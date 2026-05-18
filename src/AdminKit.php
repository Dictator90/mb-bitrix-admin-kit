<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit;

use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

/**
 * Stable package facade for creating scoped AdminKit managers.
 */
final class AdminKit
{
    public static function forModule(string|object $module): AdminKitManager
    {
        return new AdminKitManager(AdminKitScope::fromModule($module));
    }

    public static function forScope(string $scopeId): AdminKitManager
    {
        return new AdminKitManager(AdminKitScope::fromScope($scopeId));
    }

    public static function fromDirectory(string $path, ?string $scopeId = null): AdminKitManager
    {
        return new AdminKitManager(AdminKitScope::fromDirectory($path, $scopeId));
    }

    /** @param string[] $paths */
    public static function fromDirectories(array $paths, ?string $scopeId = null): AdminKitManager
    {
        return new AdminKitManager(AdminKitScope::fromDirectories($paths, $scopeId));
    }

    /** @param string|object|AdminKitScope $scope */
    public static function manager(string|object $scope): AdminKitManager
    {
        if ($scope instanceof AdminKitScope) {
            return new AdminKitManager($scope);
        }

        return self::forModule($scope);
    }
}
