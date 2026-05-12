<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;

class NumberFilter extends Filter
{
    public function getType(): string
    {
        return 'number';
    }

    public function apply(array $filter, mixed $value): array
    {
        if (is_array($value)) {
            if (isset($value['from']) && $value['from'] !== '') {
                $filter['>=' . $this->column] = $value['from'];
            }
            if (isset($value['to']) && $value['to'] !== '') {
                $filter['<=' . $this->column] = $value['to'];
            }
        } elseif ($value !== '' && $value !== null) {
            $filter[$this->column] = $value;
        }

        return $filter;
    }
}
