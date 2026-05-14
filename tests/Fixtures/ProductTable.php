<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductTable
{
    public static array $rows = [['ID' => 1, 'NAME' => 'One']];
    public static array $lastParams = [];
    public static array $lastAdded = [];
    public static array $lastUpdated = [];
    public static mixed $lastDeleted = null;
    public static array $nextAddErrors = [];
    public static array $nextUpdateErrors = [];
    public static array $nextDeleteErrors = [];

    public static function getList(array $params = []): FakeQueryResult
    {
        self::$lastParams = $params;
        return new FakeQueryResult(self::$rows);
    }

    public static function getCount(array $filter = []): int
    {
        return count(self::$rows);
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
        if (self::$nextUpdateErrors !== []) {
            return new FakeOrmResult(false, $id, self::$nextUpdateErrors);
        }

        self::$lastUpdated = ['id' => $id, 'data' => $data];
        return new FakeOrmResult(true, $id);
    }

    public static function delete($id): FakeOrmResult
    {
        if (self::$nextDeleteErrors !== []) {
            return new FakeOrmResult(false, $id, self::$nextDeleteErrors);
        }

        self::$lastDeleted = $id;
        return new FakeOrmResult(true, $id);
    }
}
