<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit;

use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\Contracts\Module\Entity as ModuleEntityContract;

/**
 * Stable package facade for creating per-module AdminKit managers.
 */
final class AdminKit
{
    public static function manager(ModuleEntityContract $module): AdminKitManager
    {
        return new AdminKitManager($module);
    }
}
