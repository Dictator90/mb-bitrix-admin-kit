<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class GridDataLoaderLoadsDataTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testItLoadsRowsIntoGrid(): void
    {
        $resource = new ProductResource();
        $grid = new Grid($resource->getGridId(), iterator_to_array($resource->indexFields()));

        $performance = (new GridDataLoader())->load($resource, $grid);

        self::assertSame(2, ProductTable::$listCalls);
        self::assertSame(1, $grid->getTotalCount());
        self::assertCount(1, $grid->getRows());
        self::assertSame(1, $performance?->rowCount);
    }
}
