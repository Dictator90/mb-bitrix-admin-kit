<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;

class CheckboxFilter extends Filter
{
    public function getType(): string
    {
        return 'checkbox';
    }

    public function apply(array $filter, mixed $value): array
    {
        if ($value !== '' && $value !== null) {
            $filter[$this->column] = $value;
        }

        return $filter;
    }
}
