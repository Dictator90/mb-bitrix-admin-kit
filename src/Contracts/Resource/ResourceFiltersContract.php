<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Contracts\FilterContract;

interface ResourceFiltersContract
{
    /** @return iterable<FilterContract> */
    public function filters(): iterable;
}
