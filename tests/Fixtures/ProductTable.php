<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductTable
{
    public static array $rows = [['ID' => 1, 'NAME' => 'One']];
    public static array $lastParams = [];
    public static function getList(array $params = []): FakeQueryResult { self::$lastParams = $params; return new FakeQueryResult(self::$rows); }
    public static function getCount(array $filter = []): int { return count(self::$rows); }
    public static function add(array $data): FakeOrmResult { self::$rows[] = ['ID' => 2] + $data; return new FakeOrmResult(true, 2); }
    public static function update($id, array $data): FakeOrmResult { return new FakeOrmResult(true, $id); }
    public static function delete($id): FakeOrmResult { return new FakeOrmResult(true, $id); }
}
