<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

use Bitrix\Main\ORM\Query\Result;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;

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
        protected ?ResourceContract $resource = null,
        protected ?GridContext $context = null,
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

        if ($this->resource instanceof ResourceContract && $this->context instanceof GridContext) {
            $dataRows = $this->resource->afterIndexRows($dataRows, $this->context);
        }

        $rows = [];
        foreach (AdminCollection::make($dataRows)->all() as $data) {
            if (is_array($data)) {
                $rows[] = $this->prepareRow($data);
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $data */
    protected function prepareRow(array $data): array
    {
        if ($this->resource instanceof ResourceContract && $this->context instanceof GridContext) {
            $data = $this->resource->mapIndexRow($data, $this->context);
        }

        foreach (AdminCollection::make($this->fields)->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                $data[$field->getColumn()] = $field->computeValue($data);
            }
        }

        $row = ['data' => $data, 'columns' => []];

        foreach (AdminCollection::make($this->fields)->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $row['columns'][$field->getColumn()] = $field->renderIndex($data[$field->getColumn()] ?? null, $data);
            $assembler = $field->getFieldAssembler();
            if ($assembler instanceof FieldAssembler) {
                $row = $assembler->processRow($row);
            }
        }

        $actions = [];
        foreach (AdminCollection::make($this->rowActions)->all() as $action) {
            if ($action instanceof RowAction) {
                $actions[] = $action->toArray($row['data'], $this->baseUrl);
            }
        }

        $row['id'] = $data[$this->primaryKey] ?? null;
        $row['actions'] = $actions;

        return $row;
    }
}
