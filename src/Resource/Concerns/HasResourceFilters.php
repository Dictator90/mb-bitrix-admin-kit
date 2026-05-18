<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceFilters
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FilterContract> */
    public function filters(): iterable
    {
        return [];
    }
}
