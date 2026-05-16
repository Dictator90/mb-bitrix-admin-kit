<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
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

        $grid = new Grid('products', [ID::make('ID', 'ID'), Text::make('Name', 'NAME')]);
        $grid->enableCollapsibleRows(true, 'NAME');
        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertTrue($params['ENABLE_COLLAPSIBLE_ROWS']);
        self::assertTrue($params['COLUMNS'][1]['shift']);
    }

    public function testAdapterAppliesGroupingAlignOnShiftColumn(): void
    {
        $grid = new Grid('products', [ID::make('ID', 'ID'), Text::make('Name', 'NAME')]);
        $grid->enableCollapsibleRows(true, 'NAME');
        $grid->setGroupingAlign('right');

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertSame('right', $params['COLUMNS'][1]['align']);
    }

    public function testAdapterDoesNotMarkShiftWhenColumnIsUnknown(): void
    {
        $grid = new Grid('products', [Text::make('Name', 'NAME')]);
        $grid->enableCollapsibleRows(true, 'MISSING');

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertFalse($params['COLUMNS'][0]['shift'] ?? false);
    }
}
