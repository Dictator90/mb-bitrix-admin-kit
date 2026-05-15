<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductTable
{
    public static array $rows = [['ID' => 1, 'NAME' => 'One']];
    public static array $lastParams = [];
    public static int $countCalls = 0;
    public static int $listCalls = 0;
    public static array $lastAdded = [];
    public static array $lastUpdated = [];
    public static mixed $lastDeleted = null;
    public static array $updatedIds = [];
    public static array $deletedIds = [];
    public static array $nextAddErrors = [];
    public static array $nextUpdateErrors = [];
    public static array $nextDeleteErrors = [];
    public static array $deleteErrorsById = [];
    public static array $updateErrorsById = [];

    public static function reset(): void
    {
        self::$rows = [['ID' => 1, 'NAME' => 'One']];
        self::$lastParams = [];
        self::$countCalls = 0;
        self::$listCalls = 0;
        self::$lastAdded = [];
        self::$lastUpdated = [];
        self::$lastDeleted = null;
        self::$updatedIds = [];
        self::$deletedIds = [];
        self::$nextAddErrors = [];
        self::$nextUpdateErrors = [];
        self::$nextDeleteErrors = [];
        self::$deleteErrorsById = [];
        self::$updateErrorsById = [];
    }

    public static function getTableName(): string
    {
        return 'vendor_product';
    }

    public static function getList(array $params = []): FakeQueryResult
    {
        self::$listCalls++;
        self::$lastParams = $params;
        $rows = self::$rows;
        $filter = $params['filter'] ?? [];

        foreach ($filter as $field => $value) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($field, $value): bool {
                if (is_array($value)) {
                    return in_array($row[$field] ?? null, $value, true);
                }

                return (string)($row[$field] ?? '') === (string)$value;
            }));
        }

        if (isset($params['limit'])) {
            $rows = array_slice($rows, (int)($params['offset'] ?? 0), (int)$params['limit']);
        }

        return new FakeQueryResult($rows);
    }

    public static function getCount(array $filter = []): int
    {
        self::$countCalls++;
        $rows = self::$rows;

        foreach ($filter as $field => $value) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($field, $value): bool {
                if (is_array($value)) {
                    return in_array($row[$field] ?? null, $value, true);
                }

                return (string)($row[$field] ?? '') === (string)$value;
            }));
        }

        return count($rows);
    }

    public static function add(array $data): FakeOrmResult
    {
        if (self::$nextAddErrors !== []) {
            return new FakeOrmResult(false, null, self::$nextAddErrors);
        }

        self::$lastAdded = $data;
        self::$rows[] = ['ID' => 2] + $data;
        return new FakeOrmResult(true, 2);
    }

    public static function update($id, array $data): FakeOrmResult
    {
        if (isset(self::$updateErrorsById[(string)$id])) {
            return new FakeOrmResult(false, $id, (array)self::$updateErrorsById[(string)$id]);
        }

        if (self::$nextUpdateErrors !== []) {
            return new FakeOrmResult(false, $id, self::$nextUpdateErrors);
        }

        self::$lastUpdated = ['id' => $id, 'data' => $data];
        self::$updatedIds[] = $id;
        return new FakeOrmResult(true, $id);
    }

    public static function delete($id): FakeOrmResult
    {
        if (isset(self::$deleteErrorsById[(string)$id])) {
            return new FakeOrmResult(false, $id, (array)self::$deleteErrorsById[(string)$id]);
        }

        if (self::$nextDeleteErrors !== []) {
            return new FakeOrmResult(false, $id, self::$nextDeleteErrors);
        }

        self::$lastDeleted = $id;
        self::$deletedIds[] = $id;

        return new FakeOrmResult(true, $id);
    }
}
