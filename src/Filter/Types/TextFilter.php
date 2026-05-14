<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;

class TextFilter extends Filter
{
    protected string $operator = 'contains';

    public function exact(): static
    {
        $this->operator = 'exact';

        return $this;
    }

    public function contains(): static
    {
        $this->operator = 'contains';

        return $this;
    }

    public function startsWith(): static
    {
        $this->operator = 'startsWith';

        return $this;
    }

    public function endsWith(): static
    {
        $this->operator = 'endsWith';

        return $this;
    }

    public function getType(): string
    {
        return 'string';
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        $key = match ($this->operator) {
            'exact' => '=' . $this->column,
            'startsWith' => $this->column . '%',
            'endsWith' => '%' . $this->column,
            default => '%' . $this->column,
        };
        $filter[$key] = $value;

        return $filter;
    }
}
