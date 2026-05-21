<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderOrderTest extends TestCase
{
    use BitrixContextTrait;

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testUiSortReplacesDefaultAndIndexOrder(): void
    {
        $resource = new class () extends ProductResource {
            public function indexOrder(GridContext $context): array
            {
                return ['SORT' => 'ASC'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, null, [
            'sort' => ['NAME' => 'DESC'],
        ]));

        self::assertSame(['NAME' => 'DESC'], $params['order']);
    }

    public function testItMergesDefaultAndIndexOrderWhenUiSortIsEmpty(): void
    {
        $resource = new class () extends ProductResource {
            public function indexOrder(GridContext $context): array
            {
                return ['SORT' => 'ASC'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource));

        self::assertSame(['ID' => 'ASC', 'SORT' => 'ASC'], $params['order']);
    }

    public function testItReadsSortFromHttpRequest(): void
    {
        $resource = new ProductResource();
        $this->setGetRequest(['by' => 'NAME', 'order' => 'asc']);
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, $request));

        self::assertSame(['NAME' => 'ASC'], $params['order']);
    }
}
