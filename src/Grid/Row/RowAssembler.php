<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;

class RowAssembler
{
    /**
     * @param FieldContract[]   $fields
     * @param ActionContract[]  $rowActions
     */
    public function __construct(
        protected array $fields,
        protected array $rowActions = [],
        protected string $baseUrl = '',
        protected string $primaryKey = 'ID',
    ) {}

    /**
     * @param \Bitrix\Main\ORM\Query\Result $result
     * @return array[]
     */
    public function buildRows($result): array
    {
        $rows = [];
        while ($data = $result->fetch()) {
            $rows[] = $this->prepareRow($data);
        }

        return $rows;
    }

    /** @param array<string, mixed> $data */
    protected function prepareRow(array $data): array
    {
        $row = ['data' => $data, 'columns' => []];

        foreach ($this->fields as $field) {
            $assembler = $field->getFieldAssembler();
            if ($assembler instanceof FieldAssembler) {
                $row = $assembler->processRow($row);
            }
        }

        $actions = [];
        foreach ($this->rowActions as $action) {
            if ($action instanceof RowAction) {
                $actions[] = $action->toArray($row['data'], $this->baseUrl);
            }
        }

        $row['id']      = $data[$this->primaryKey] ?? null;
        $row['actions'] = $actions;

        return $row;
    }
}
