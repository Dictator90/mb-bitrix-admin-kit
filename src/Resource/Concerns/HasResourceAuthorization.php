<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;

trait HasResourceAuthorization
{
    public function canCreate(?PermissionContext $context = null): bool
    {
        return true;
    }

    public function canUpdate(PermissionContext|DataWrapper|null $context = null): bool
    {
        return true;
    }

    public function canDelete(PermissionContext|DataWrapper|null $context = null): bool
    {
        return true;
    }

    public function canView(?PermissionContext $context = null): bool
    {
        return true;
    }
}
