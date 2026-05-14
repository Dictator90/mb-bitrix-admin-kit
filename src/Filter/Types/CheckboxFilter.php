<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;

class CheckboxFilter extends Filter
{
    public function getType(): string
    {
        return 'checkbox';
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        $filter[$this->column] = $value;

        return $filter;
    }
}
