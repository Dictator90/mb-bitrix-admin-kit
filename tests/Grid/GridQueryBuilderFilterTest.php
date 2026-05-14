<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Filter\Types\NumberFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderFilterTest extends TestCase
{
    public function testItMergesDefaultUiAndIndexFilters(): void
    {
        $resource = new class extends ProductResource {
            public function filters(): iterable
            {
                return [TextFilter::make('Name', 'NAME')->exact(), NumberFilter::make('Price', 'PRICE')->greaterThan()];
            }

            public function defaultFilter(): array
            {
                return ['ACTIVE' => 'Y'];
            }

            public function indexFilter(GridContext $context): array
            {
                return ['SITE_ID' => 's1'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, null, [
            'filter' => ['NAME' => 'Phone', 'PRICE' => 100],
        ]));

        self::assertSame(['ACTIVE' => 'Y', '=NAME' => 'Phone', '>PRICE' => 100, 'SITE_ID' => 's1'], $params['filter']);
    }
}
