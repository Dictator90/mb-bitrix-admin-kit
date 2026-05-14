<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Database\RelationResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class LookupCacheTest extends TestCase
{
    public function testLookupPersistentCacheAvoidsRepeatedQueriesAcrossResolverInstances(): void
    {
        ArrayTtlCache::clear();
        ProductTable::reset();

        (new RelationResolver())->cache(3600)->resolve(ProductTable::class, 1, 'ID', ['ID', 'NAME']);
        (new RelationResolver())->cache(3600)->resolve(ProductTable::class, 1, 'ID', ['ID', 'NAME']);

        self::assertSame(1, ProductTable::$listCalls);
        self::assertSame(['filter' => ['@ID' => ['1']], 'select' => ['ID', 'NAME']], ProductTable::$lastParams);
    }
}
