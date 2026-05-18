<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;

trait HasResourceGrouping
{
    public function indexGrouping(): ?IndexGrouping
    {
        return null;
    }
}
