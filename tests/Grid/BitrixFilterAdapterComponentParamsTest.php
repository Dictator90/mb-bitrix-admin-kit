<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixFilterAdapter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;

final class BitrixFilterAdapterComponentParamsTest extends TestCase
{
    public function testItBuildsMainUiFilterParams(): void
    {
        $grid = new Grid('products', [], [TextFilter::make('Name', 'NAME')]);

        $params = (new BitrixFilterAdapter())->componentParams($grid);

        self::assertSame('products_filter', $params['FILTER_ID']);
        self::assertSame('products', $params['GRID_ID']);
        self::assertCount(1, $params['FILTER']);
        self::assertTrue($params['ENABLE_LIVE_SEARCH']);
        self::assertTrue($params['ENABLE_LABEL']);
        self::assertTrue($params['RESET_TO_DEFAULT_MODE']);
    }

    public function testItReturnsNullWithoutFilters(): void
    {
        self::assertNull((new BitrixFilterAdapter())->componentParams(new Grid('products')));
    }
}
