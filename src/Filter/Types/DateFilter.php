<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;

class DateFilter extends Filter
{
    protected bool $withTime = false;
    protected string $operator = 'range';

    public function withTime(bool $withTime = true): static
    {
        $this->withTime = $withTime;

        return $this;
    }

    public function exact(): static
    {
        $this->operator = 'exact';

        return $this;
    }

    public function range(): static
    {
        $this->operator = 'range';

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

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        if ($this->operator === 'exact') {
            $filter[$this->column] = $value;

            return $filter;
        }

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
