<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;

interface ResourcePermissionContract
{
    public function canView(?PermissionContext $context = null): bool;

    public function canCreate(?PermissionContext $context = null): bool;

    public function canUpdate(PermissionContext|DataWrapper|null $context = null): bool;

    public function canDelete(PermissionContext|DataWrapper|null $context = null): bool;
}
