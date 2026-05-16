<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter;
use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;

final class GridCollapsibleRowsTest extends TestCase
{
    public function testGridStoresCollapsibleRowsFlag(): void
    {
        $grid = new Grid('products');

        self::assertFalse($grid->hasCollapsibleRows());
        $grid->enableCollapsibleRows();
        self::assertTrue($grid->hasCollapsibleRows());
        $grid->enableCollapsibleRows(false);
        self::assertFalse($grid->hasCollapsibleRows());
    }

    public function testAdapterAddsCollapsibleParamOnlyWhenEnabled(): void
    {
        $grid = new Grid('products');
        self::assertArrayNotHasKey('ENABLE_COLLAPSIBLE_ROWS', (new BitrixGridAdapter())->componentParams($grid));

        $grid->enableCollapsibleRows();
        self::assertTrue((new BitrixGridAdapter())->componentParams($grid)['ENABLE_COLLAPSIBLE_ROWS']);
    }
}
