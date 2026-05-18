<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Grid;

use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class BitrixFilterAdapter
{
    /** @return array<string,mixed>|null */
    public function componentParams(Grid $grid): ?array
    {
        if ($grid->getFilters() === []) {
            return null;
        }

        return [
            'FILTER_ID' => $grid->getFilterId(),
            'GRID_ID' => $grid->getId(),
            'FILTER' => array_map(
                static fn (FilterContract $filter): array => $filter->getFilterFieldConfig(),
                AdminCollection::make($grid->getFilters())->all(),
            ),
            'ENABLE_LIVE_SEARCH' => true,
            'ENABLE_LABEL' => true,
            'RESET_TO_DEFAULT_MODE' => true,
        ];
    }
}
