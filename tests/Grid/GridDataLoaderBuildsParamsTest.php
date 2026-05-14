<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class GridDataLoaderBuildsParamsTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testItBuildsGuardedParamsThroughQueryBuilder(): void
    {
        $resource = new ProductResource();
        $grid = new Grid($resource->getGridId(), iterator_to_array($resource->indexFields()), iterator_to_array($resource->filters()));

        $performance = (new GridDataLoader())->load($resource, $grid);

        self::assertNotNull($performance);
        self::assertSame(['ID', 'NAME'], ProductTable::$lastParams['select']);
        self::assertSame(['ID' => 'ASC'], ProductTable::$lastParams['order']);
        self::assertSame(20, ProductTable::$lastParams['limit']);
        self::assertSame(ProductTable::$lastParams, $performance->params);
    }
}
