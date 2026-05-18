<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

use MB\Bitrix\AdminKit\UI\EntitySelector\IblockElementListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockPropertyListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockSectionListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\UserGroupListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\UserListProvider;

final class EntitySelectorProviderResolver
{
    /** @param array<int,array<string,mixed>> $entities */
    public function resolveProviderClass(string $entityId, array $entities = []): ?string
    {
        if ($entities !== []) {
            $first = $entities[0] ?? null;
            if (is_array($first) && is_string($first['id'] ?? null) && $first['id'] !== '') {
                $entityId = $first['id'];
            }
        }

        return match ($entityId) {
            'user', 'user-list' => UserListProvider::class,
            'user-group', 'user-group-list' => UserGroupListProvider::class,
            'iblock', 'iblock-list' => IblockListProvider::class,
            'iblock-property', 'iblock-property-list' => IblockPropertyListProvider::class,
            'iblock-element', 'iblock-element-list' => IblockElementListProvider::class,
            'iblock-section', 'iblock-section-list' => IblockSectionListProvider::class,
            default => null,
        };
    }
}
