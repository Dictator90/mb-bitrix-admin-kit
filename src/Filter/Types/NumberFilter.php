<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;

class NumberFilter extends Filter
{
    protected string $operator = 'range';

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

    public function greaterThan(): static
    {
        $this->operator = 'greaterThan';

        return $this;
    }

    public function lessThan(): static
    {
        $this->operator = 'lessThan';

        return $this;
    }

    public function getType(): string
    {
        return 'number';
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        if (is_array($value)) {
            $from = $value['from'] ?? null;
            $to = $value['to'] ?? null;

            if (($this->operator === 'range' || $this->operator === 'greaterThan') && !$this->isEmpty($from)) {
                $filter['>=' . $this->column] = $from;
            }
            if (($this->operator === 'range' || $this->operator === 'lessThan') && !$this->isEmpty($to)) {
                $filter['<=' . $this->column] = $to;
            }

            return $filter;
        }

        $key = match ($this->operator) {
            'greaterThan' => '>' . $this->column,
            'lessThan' => '<' . $this->column,
            default => $this->column,
        };
        $filter[$key] = $value;

        return $filter;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
