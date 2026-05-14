<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CountCacheTest extends TestCase
{
    public function testCountIsCachedWhenTtlConfigured(): void
    {
        ArrayTtlCache::clear();
        ProductTable::reset();
        $resource = new class extends ProductResource {
            public function countCacheTtl(GridContext $context): int { return 3600; }
        };
        $page = new class($resource) extends IndexPage {
            public function run(Grid $grid): void { $this->loadData($grid); }
        };
        $grid = new Grid($resource->getGridId(), iterator_to_array($resource->indexFields()), [], [], '', 'ID');

        $page->run($grid);
        $page->run($grid);

        self::assertSame(1, ProductTable::$countCalls);
    }
}
