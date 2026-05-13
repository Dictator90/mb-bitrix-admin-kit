<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderTest extends TestCase
{
    public function testItBuildsOrmParams(): void
    {
        $resource = new ProductResource();
        $ctx = GridContext::make($resource, null, ['filter' => ['NAME' => 'foo'], 'limit' => 5]);
        $params = (new GridQueryBuilder())->build($resource, $ctx);
        self::assertSame(['ID', 'NAME'], $params['select']);
        self::assertSame(['%NAME' => 'foo'], $params['filter']);
        self::assertSame(5, $params['limit']);
    }
}
