<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class GroupedRowsBuilderGroupTable
{
    public static array $rows = [];
    public static array $lastParams = [];

    public static function getList(array $params): FakeQueryResult
    {
        self::$lastParams = $params;
        $ids = $params['filter']['@ID'] ?? [];
        $rows = array_values(array_filter(self::$rows, static fn (array $row): bool => in_array($row['ID'], $ids, true)));

        return new FakeQueryResult($rows);
    }
}
