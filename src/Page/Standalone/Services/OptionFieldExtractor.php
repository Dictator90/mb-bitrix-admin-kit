<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone\Services;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;

final class OptionFieldExtractor
{
    /**
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     * @return FieldContract[]
     */
    public function extract(array $components): array
    {
        $fields = [];
        foreach ($components as $item) {
            if ($item instanceof Tab) {
                $fields = array_merge($fields, $this->extract($item->getItems()));
            } elseif ($item instanceof FieldContainerContract) {
                $fields = array_merge($fields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }

        return $fields;
    }
}
