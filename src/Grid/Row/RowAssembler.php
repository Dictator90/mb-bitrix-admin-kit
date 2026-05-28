<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

use Bitrix\Main\ORM\Query\Result;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Grouping\GroupedRowsBuilder;
use MB\Bitrix\AdminKit\Grid\Grouping\GroupLabelRenderer;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Relations\FieldRelationLoader;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

class RowAssembler
{
    /**
     * @param FieldContract[] $fields
     * @param ActionContract[] $rowActions
     */
    public function __construct(
        protected array $fields,
        protected array $rowActions = [],
        protected string $baseUrl = '',
        protected string $primaryKey = 'ID',
        protected ?CrudResourceContract $resource = null,
        protected ?GridContext $context = null,
        protected ?IndexPageDefinitionContract $indexPage = null,
    ) {
    }

    /**
     * @param Result $result
     * @return array[]
     */
    public function buildRows($result): array
    {
        $dataRows = [];
        while ($data = $result->fetch()) {
            $dataRows[] = $data;
        }

        if ($this->resource instanceof CrudResourceContract && $this->context instanceof GridContext) {
            $dataRows = ($this->indexPage ?? $this->resource)->afterIndexRows($dataRows, $this->context);
            $dataRows = (new FieldRelationLoader())->load($dataRows, $this->fields);

            $grouping = $this->indexPage?->grouping();
            if ($grouping instanceof IndexGrouping && $this->resource instanceof ResourceOrmContract) {
                $dataRows = (new GroupedRowsBuilder())->build($dataRows, $this->resource, $grouping, $this->context, $this->indexPage, $this->fields);
            }
        }

        $rows = [];
        foreach ($dataRows as $data) {
            if (is_array($data)) {
                $rows[] = $this->prepareRow($data);
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $data */
    protected function prepareRow(array $data): array
    {
        $isGroupRow = ($data['__ROW_TYPE'] ?? null) === 'group';
        $meta = is_array($data['__adminkit_grid_row'] ?? null) ? $data['__adminkit_grid_row'] : [];
        unset($data['__adminkit_grid_row']);

        if (!$isGroupRow && $this->resource instanceof CrudResourceContract && $this->context instanceof GridContext) {
            $data = ($this->indexPage ?? $this->resource)->mapIndexRow($data, $this->context);
        }

        foreach ($this->fields as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                $data[$field->getColumn()] = $field->computeValue($data);
            }
        }

        $row = ['data' => $data, 'columns' => []];

        foreach ($this->fields as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $value = $field->resolveValue($data, $data);
            $rendered = $this->resource instanceof CrudResourceContract
                ? $field->renderIndex(new FieldRenderContext(
                    field: $field,
                    resource: $this->resource,
                    item: $data,
                    value: $value,
                    page: 'index',
                    row: $data,
                ))
                : $field->renderIndex($value, $data);

            if ($isGroupRow) {
                $grouping = $this->indexPage?->grouping();
                if ($grouping instanceof IndexGrouping && $field->getColumn() === ($grouping->labelColumn() ?? $this->firstFieldColumn())) {
                    $rendered = (new GroupLabelRenderer())->render($data, $grouping, $this->baseUrl);
                } elseif (($data[$field->getColumn()] ?? null) === null) {
                    $rendered = '';
                }
            } elseif (method_exists($field, 'shouldRenderAsEditLink') && $field->shouldRenderAsEditLink()) {
                $rendered = $this->wrapEditLink($rendered, $data);
            }

            $row['columns'][$field->getColumn()] = $rendered;
            $assembler = $field->getFieldAssembler();
            if ($assembler instanceof FieldAssembler) {
                $row = $assembler->processRow($row);
            }
        }

        $actions = [];
        if (!$isGroupRow) {
            foreach ($this->rowActions as $action) {
                if ($action instanceof RowAction) {
                    $actions[] = $action->toArray($row['data'], $this->baseUrl, $this->context?->gridId);
                }
            }
        }

        $row['id'] = $data['__GRID_ROW_ID'] ?? ($data[$this->primaryKey] ?? null);
        $row['actions'] = $actions;

        foreach (['shift', 'depth', 'parent_id', 'parent_group_id', 'group_id', 'has_child', 'expand'] as $key) {
            if (!array_key_exists($key, $meta)) {
                continue;
            }

            $value = $meta[$key];
            $row[$key] = in_array($key, ['shift', 'has_child', 'expand'], true)
                ? (bool)$value
                : $value;
        }

        $grouping = $this->indexPage?->grouping();
        if ($isGroupRow && $grouping instanceof IndexGrouping && $grouping->fullWidth()) {
            $labelColumn = $grouping->labelColumn() ?? $this->firstFieldColumn();
            $labelHtml = $labelColumn !== null ? (string)($row['columns'][$labelColumn] ?? '') : '';
            $depth = is_int($meta['depth'] ?? null) ? (int)$meta['depth'] : 0;
            $depthClass = 'adminkit-grid-group-label--depth-' . max(0, min($depth, 20));
            $row['custom'] = '<span class="adminkit-grid-group-label ' . $depthClass . '">' . $labelHtml . '</span>';
            $rawGroupId = (string)($data['__GROUP_ID'] ?? '');
            $row['group_id'] = $rawGroupId;
            $row['align'] = $grouping->align();
            $row['attrs'] = is_array($row['attrs'] ?? null) ? $row['attrs'] : [];
            $row['attrs']['data-group'] = null;
            $row['attrs']['data-group-id'] = $rawGroupId;
            $row['attrs']['data-align'] = $grouping->align();
            unset($row['shift']);
        }

        if (isset($row['group_id']) && $row['group_id'] !== '' && $row['group_id'] !== null) {
            $row['attrs'] = is_array($row['attrs'] ?? null) ? $row['attrs'] : [];
            $row['attrs']['data-group-id'] = (string)$row['group_id'];
        }

        return $row;
    }

    private function firstFieldColumn(): ?string
    {
        foreach ($this->fields as $field) {
            if ($field instanceof FieldContract) {
                return $field->getColumn();
            }
        }

        return null;
    }

    /** @param array<string,mixed> $data */
    private function wrapEditLink(string $rendered, array $data): string
    {
        if (!$this->resource instanceof CrudResourceContract) {
            return $rendered;
        }
        $id = $data['__REAL_ID'] ?? $data[$this->primaryKey] ?? null;
        if ($id === null || $id === '') {
            return $rendered;
        }

        $url = (new UrlGenerator($this->baseUrl))->editUrl($id);
        $onclick = '';
        if ($this->resource->editInSidePanel()) {
            $width = $this->resource->sidePanelWidth();
            $onclick = ' onclick="BX.SidePanel.Instance.open(this.href, {width: ' . $width . '}); return false;"';
        }

        return '<a href="' . htmlspecialchars($url) . '"' . $onclick . '>' . $rendered . '</a>';
    }
}
