<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

final class GridRowId
{
    public static function group(mixed $id): string
    {
        return 'group:' . (string)$id;
    }

    public static function item(mixed $id): string
    {
        return 'item:' . (string)$id;
    }

    public static function isGroupId(mixed $id): bool
    {
        return is_string($id) && str_starts_with($id, 'group:');
    }

    public static function isItemId(mixed $id): bool
    {
        return is_string($id) && str_starts_with($id, 'item:');
    }

    public static function normalizeItemId(mixed $id): mixed
    {
        if (self::isGroupId($id)) {
            return null;
        }
        if (self::isItemId($id)) {
            return substr((string)$id, 5);
        }

        return $id;
    }

    public static function rawId(mixed $id): mixed
    {
        if (self::isGroupId($id) || self::isItemId($id)) {
            return substr((string)$id, strpos((string)$id, ':') + 1);
        }

        return $id;
    }
}
