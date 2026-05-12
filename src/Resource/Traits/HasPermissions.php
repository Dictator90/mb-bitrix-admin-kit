<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Traits;

use MB\Bitrix\AdminKit\Support\DataWrapper;

trait HasPermissions
{
    public function canCreate(): bool
    {
        return true;
    }

    public function canUpdate(?DataWrapper $item = null): bool
    {
        return true;
    }

    public function canDelete(?DataWrapper $item = null): bool
    {
        return true;
    }

    public function canView(): bool
    {
        return true;
    }
}
