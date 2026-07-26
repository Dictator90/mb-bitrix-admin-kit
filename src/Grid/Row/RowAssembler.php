<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

use Bitrix\Main\ORM\Query\Result;
use CUtil;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceActionsContract;
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
            $sidePanelWidth = $this->resource !== null && method_exists($this->resource, 'sidePanelWidth')
                ? (int)$this->resource->sidePanelWidth()
                : null;

            foreach ($this->rowActions as $action) {
                if ($action instanceof RowAction) {
                    $actions[] = $action->toArray($row['data'], $this->baseUrl, $this->context?->gridId, $sidePanelWidth);
                }
            }
        } else {
            $actions = $this->buildGroupActions($data);
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
            // Кнопка контекстного меню рендерится ПЕРЕД меткой, чтобы и
            // [⋮], и название группы шли в одну строку (см. CSS:
            // .adminkit-grid-group-{label,actions-button} → display: inline*).
            $row['custom'] = '';
            if ($actions !== []) {
                $row['custom'] .= $this->renderGroupActionsButton($actions);
            }
            $row['custom'] .= '<span class="adminkit-grid-group-label ' . $depthClass . '">' . $labelHtml . '</span>';
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

    /**
     * Build row actions for a grouping header row by delegating to the grouping resource's
     * {@see ResourceActionsContract::rowActions()}; URLs target the grouping resource's page.
     *
     * @param array<string,mixed> $data
     * @return array<int,array<string,mixed>>
     */
    private function buildGroupActions(array $data): array
    {
        $grouping = $this->indexPage?->grouping();
        if (!$grouping instanceof IndexGrouping) {
            return [];
        }

        $groupId = $data['__GROUP_ID'] ?? null;
        // Ungrouped/empty header rows are synthetic — they have no resource record to act on.
        if ($groupId === null || $groupId === '' || $groupId === '__ungrouped') {
            return [];
        }

        $resourceClass = $grouping->resourceClass();
        $groupResource = new $resourceClass();
        if (!$groupResource instanceof ResourceActionsContract) {
            return [];
        }

        $groupData = is_array($data['__GROUP_DATA'] ?? null) ? $data['__GROUP_DATA'] : [];
        $rowDataForAction = $groupData + ['ID' => $groupId];

        $baseUrlForActions = (new UrlGenerator($this->baseUrl))->resourceUrl($resourceClass::getId());

        $sidePanelWidth = method_exists($groupResource, 'sidePanelWidth')
            ? (int)$groupResource->sidePanelWidth()
            : null;

        $gridId = $this->context?->gridId;

        $actions = [];
        foreach ($groupResource->rowActions() as $action) {
            if (!$action instanceof RowAction) {
                continue;
            }
            $arr = $action->toArray($rowDataForAction, $baseUrlForActions, $gridId, $sidePanelWidth);

            // Delete у item-строки делает window.location.href и редиректит на baseListUrl()
            // ресурса. Для group-строки это уводило бы пользователя на список типов вместо
            // того, чтобы остаться на исходном гриде. Перехватываем onclick и удаляем
            // запись запросом BX.ajax, после чего перезагружаем текущий грид по gridId.
            if ($action->getType() === 'delete' && $gridId !== null && $gridId !== '') {
                $arr = $this->makeAsyncDeleteAction($arr, $action, $rowDataForAction, $baseUrlForActions, $gridId);
            }

            $actions[] = $arr;
        }

        return $actions;
    }

    /**
     * @param array<string,mixed> $actionArr
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function makeAsyncDeleteAction(array $actionArr, RowAction $action, array $row, string $baseUrl, string $gridId): array
    {
        $id = (int)($row['ID'] ?? $row['id'] ?? 0);
        if ($id <= 0) {
            return $actionArr;
        }

        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        $deleteUrl = $baseUrl . $sep . 'action=delete&id=' . $id . '&sessid=' . (function_exists('bitrix_sessid') ? bitrix_sessid() : '');

        $urlJs = CUtil::JSEscape($deleteUrl);
        $confirmJs = CUtil::JSEscape((string)($action->getConfirmText() ?? ''));
        $gridIdJson = json_encode($gridId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '""';

        // BX.ajax.get «съест» LocalRedirect ответа (это нормально — нам нужен только факт
        // успешного удаления). После — достаём грид через gridManager и зовём reloadTable().
        $actionArr['onclick'] =
            "if(confirm('{$confirmJs}')){"
            . "BX.ajax.get('{$urlJs}',function(){"
            .   'var manager=BX.Main&&BX.Main.gridManager?BX.Main.gridManager:null;'
            .   'if(!manager){return;}'
            .   "var pair=manager.getById?manager.getById({$gridIdJson}):null;"
            .   'var grid=pair&&(pair.instance||pair.grid)?(pair.instance||pair.grid):null;'
            .   "if(!grid&&manager.getInstanceById){grid=manager.getInstanceById({$gridIdJson});}"
            .   "if(grid&&typeof grid.reloadTable==='function'){grid.reloadTable();}"
            . '});'
            . '}';

        return $actionArr;
    }

    /**
     * Render the context-menu hook for {@see IndexGrouping::fullWidth()} group rows.
     *
     * Full-width group rows are emitted by main.ui.grid as <td colspan="…">…</td> with
     * no built-in actions cell, so the popup hook must live inside the custom HTML to
     * be discovered by Bitrix's row.js (`getActionsButton`).
     *
     * @param array<int,array<string,mixed>> $actions
     */
    private function renderGroupActionsButton(array $actions): string
    {
        $json = json_encode($actions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '';
        }

        return '<a href="#" class="main-grid-row-action-button adminkit-grid-group-actions-button" data-actions="'
            . htmlspecialchars($json, ENT_QUOTES, 'UTF-8')
            . '"></a>';
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
