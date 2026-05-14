<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderOrderTest extends TestCase
{
    public function testItMergesDefaultUiAndIndexOrder(): void
    {
        $resource = new class extends ProductResource {
            public function indexOrder(GridContext $context): array
            {
                return ['SORT' => 'ASC'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, null, [
            'sort' => ['NAME' => 'DESC'],
        ]));

        self::assertSame(['ID' => 'ASC', 'NAME' => 'DESC', 'SORT' => 'ASC'], $params['order']);
    }
}
