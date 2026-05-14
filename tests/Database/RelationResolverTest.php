<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\RelationResolver;
use PHPUnit\Framework\TestCase;

final class RelationResolverTest extends TestCase
{
    public function testPreloadCachesRowsAndAvoidsNPlusOne(): void
    {
        FakeRelationTable::$calls = 0;
        $resolver = new RelationResolver();

        $first = $resolver->preload(FakeRelationTable::class, [1, 2], 'ID', ['ID', 'NAME']);
        $second = $resolver->preload(FakeRelationTable::class, [2, 1], 'ID', ['ID', 'NAME']);

        self::assertSame('First', $first['1']['NAME']);
        ksort($first);
        ksort($second);
        self::assertSame($first, $second);
        self::assertSame(1, FakeRelationTable::$calls);
    }
}

final class FakeRelationTable
{
    public static int $calls = 0;

    public static function getList(array $params): FakeRelationResult
    {
        self::$calls++;
        $rows = [];
        foreach ($params['filter']['@ID'] as $id) {
            $rows[] = ['ID' => (string)$id, 'NAME' => $id == 1 ? 'First' : 'Second'];
        }

        return new FakeRelationResult($rows);
    }
}

final class FakeRelationResult
{
    public function __construct(private array $rows)
    {
    }

    public function fetch(): ?array
    {
        return array_shift($this->rows);
    }
}
