<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;

interface ResourceGroupingContract
{
    public function indexGrouping(): ?IndexGrouping;
}
