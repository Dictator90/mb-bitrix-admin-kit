<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Relation\HasMany;
use MB\Bitrix\AdminKit\Field\Relation\HasOne;
use MB\Bitrix\AdminKit\Grid\Relations\FieldRelationLoader;
use MB\Bitrix\AdminKit\Tests\Fixtures\FakeQueryResult;
use PHPUnit\Framework\TestCase;

final class FieldRelationLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        RelationSiteTable::$rows = [
            ['COOKIE_ID' => 1, 'SITE_ID' => 's1', 'SORT' => 20],
            ['COOKIE_ID' => 1, 'SITE_ID' => 's2', 'SORT' => 10],
            ['COOKIE_ID' => 2, 'SITE_ID' => 's3', 'SORT' => 30],
        ];
        RelationSiteTable::$lastParams = [];
    }

    public function testHasManyLoadsMultipleValuesAndDefaults(): void
    {
        $field = HasMany::make('Sites', 'SITES')
            ->table(RelationSiteTable::class)
            ->foreignKey('COOKIE_ID')
            ->localKey('ID')
            ->value('SITE_ID')
            ->order(['SORT' => 'ASC']);

        $rows = (new FieldRelationLoader())->load([['ID' => 1], ['ID' => 3]], [$field]);

        self::assertSame(['s1', 's2'], $rows[0]['SITES']);
        self::assertSame([], $rows[1]['SITES']);
        self::assertSame(['SORT' => 'ASC'], RelationSiteTable::$lastParams['order']);
    }

    public function testHasOneLoadsFirstValueAndDefault(): void
    {
        $field = HasOne::make('Site', 'SITE')
            ->table(RelationSiteTable::class)
            ->foreignKey('COOKIE_ID')
            ->localKey('ID')
            ->value(static fn (array $row): string => $row['SITE_ID'] . '!')
            ->default('none');

        $rows = (new FieldRelationLoader())->load([['ID' => 1], ['ID' => 3]], [$field]);

        self::assertSame('s1!', $rows[0]['SITE']);
        self::assertSame('none', $rows[1]['SITE']);
    }

    public function testItSkipsGroupRows(): void
    {
        $field = HasMany::make('Sites', 'SITES')
            ->table(RelationSiteTable::class)
            ->foreignKey('COOKIE_ID')
            ->localKey('ID')
            ->value('SITE_ID');

        $rows = (new FieldRelationLoader())->load([['__ROW_TYPE' => 'group', 'ID' => 1]], [$field]);

        self::assertArrayNotHasKey('SITES', $rows[0]);
    }
}

final class RelationSiteTable
{
    public static array $rows = [];
    public static array $lastParams = [];

    public static function getList(array $params): FakeQueryResult
    {
        self::$lastParams = $params;
        $ids = $params['filter']['@COOKIE_ID'] ?? [];
        $rows = array_values(array_filter(self::$rows, static fn (array $row): bool => in_array($row['COOKIE_ID'], $ids, true)));

        return new FakeQueryResult($rows);
    }
}
