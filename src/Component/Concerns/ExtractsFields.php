<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Concerns;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;

trait ExtractsFields
{
    /** @return list<FieldContract> */
    public function extractFields(): array
    {
        $fields = [];

        foreach ($this->children as $child) {
            if ($child instanceof FieldContract) {
                $fields[] = $child;
                continue;
            }

            if ($child instanceof FieldContainerContract) {
                $fields = array_merge($fields, $child->extractFields());
            }
        }

        return $fields;
    }
}
