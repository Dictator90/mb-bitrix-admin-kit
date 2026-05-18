<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class GridDataLoaderCountCacheTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        ArrayTtlCache::clear();
    }

    public function testItCachesTotalCount(): void
    {
        $resource = new class () extends ProductResource {
            public function countCacheTtl(GridContext $context): int
            {
                return 60;
            }
        };
        $grid = new Grid($resource->getGridId(), iterator_to_array($resource->indexFields()));
        $loader = new GridDataLoader();

        $first = $loader->load($resource, $grid);
        $second = $loader->load($resource, $grid);

        self::assertSame(1, ProductTable::$countCalls);
        self::assertFalse($first?->cacheUsed);
        self::assertTrue($second?->cacheUsed);
    }
}
