<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Column;

use MB\Bitrix\AdminKit\Contracts\FieldContract;

class ColumnDataProvider
{
    /** @param FieldContract[] $fields */
    public function __construct(protected array $fields) {}

    /** @return array[] */
    public function getColumns(): array
    {
        return array_map(fn(FieldContract $f) => $f->getGridColumnConfig(), $this->fields);
    }
}
