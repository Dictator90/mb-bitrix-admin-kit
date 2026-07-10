<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use Bitrix\Highloadblock\DataManager as HighloadDataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use Throwable;

/**
 * Resolves which columns of a DataManager are UserField "file" columns
 * (USER_TYPE_ID = 'file').
 *
 * UserField file columns (Highload-block UF_* files, and other UF-enabled
 * entities) reject a pre-saved integer file id on save — the UserField save
 * layer only persists a fresh file array. AdminKit uses this map to make the
 * {@see \MB\Bitrix\AdminKit\Field\File} field hand the ORM a file array for
 * those columns instead of an already-saved id.
 */
final class UserFieldFileColumns
{
    /** @var array<string, array<string, true>> */
    private static array $cache = [];

    /**
     * @param class-string $dataManagerClass
     * @return array<string, true> Map of columnName => true for UF file columns.
     */
    public static function forDataManager(string $dataManagerClass): array
    {
        if (isset(self::$cache[$dataManagerClass])) {
            return self::$cache[$dataManagerClass];
        }

        $columns = [];
        $entityId = self::resolveUfEntityId($dataManagerClass);

        if ($entityId !== null && class_exists(UserFieldTable::class)) {
            try {
                $rows = UserFieldTable::getList([
                    'filter' => ['=ENTITY_ID' => $entityId, '=USER_TYPE_ID' => 'file'],
                    'select' => ['FIELD_NAME'],
                ]);
                while ($row = $rows->fetch()) {
                    $columns[(string)$row['FIELD_NAME']] = true;
                }
            } catch (Throwable) {
                $columns = [];
            }
        }

        return self::$cache[$dataManagerClass] = $columns;
    }

    /**
     * @param class-string $dataManagerClass
     */
    private static function resolveUfEntityId(string $dataManagerClass): ?string
    {
        if (method_exists($dataManagerClass, 'getUfId')) {
            $ufId = $dataManagerClass::getUfId();
            if (is_string($ufId) && $ufId !== '') {
                return $ufId;
            }
        }

        if (
            Loader::includeModule('highloadblock')
            && is_subclass_of($dataManagerClass, HighloadDataManager::class)
        ) {
            try {
                $name = $dataManagerClass::getEntity()->getName();
                $hlblock = HighloadBlockTable::getList([
                    'filter' => ['=NAME' => $name],
                    'select' => ['ID'],
                    'limit' => 1,
                ])->fetch();

                if ($hlblock) {
                    return 'HLBLOCK_' . $hlblock['ID'];
                }
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
