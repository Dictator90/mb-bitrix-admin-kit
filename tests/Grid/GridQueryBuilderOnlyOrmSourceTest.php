<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GridQueryBuilderOnlyOrmSourceTest extends TestCase
{
    public function testQueryBuilderBuildsOnlyOrmParams(): void
    {
        $params = (new GridQueryBuilder())->build(new ProductResource(), \MB\Bitrix\AdminKit\Grid\GridContext::make(new ProductResource()));

        self::assertSame(['select', 'filter', 'order', 'limit', 'offset'], array_keys($params));
        self::assertFalse((new ReflectionClass(Grid::class))->hasMethod('getOrmParams'));
    }
}
