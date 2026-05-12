<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;

class DateFilter extends Filter
{
    protected bool $withTime = false;

    public function withTime(bool $withTime = true): static
    {
        $this->withTime = $withTime;

        return $this;
    }

    public function getType(): string
    {
        return 'date';
    }

    public function prepareFieldData(): array
    {
        return ['time' => $this->withTime];
    }

    public function apply(array $filter, mixed $value): array
    {
        if (is_array($value)) {
            if (!empty($value['from'])) {
                $filter['>=' . $this->column] = $value['from'];
            }
            if (!empty($value['to'])) {
                $filter['<=' . $this->column] = $value['to'];
            }
        }

        return $filter;
    }
}
