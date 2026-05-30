<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridSettings;
use PHPUnit\Framework\TestCase;

final class BitrixGridAdapterComponentParamsTest extends TestCase
{
    public function testItBuildsMainUiGridParams(): void
    {
        $grid = new Grid('products', [ID::make('ID'), Text::make('Name', 'NAME')->editable()]);
        $grid->setTotalCount(5);
        $grid->setBulkActions([BulkAction::make('archive', 'Archive')]);

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertSame('products', $params['GRID_ID']);
        self::assertCount(2, $params['COLUMNS']);
        self::assertSame(5, $params['TOTAL_ROWS_COUNT']);
        self::assertTrue($params['SHOW_ROW_CHECKBOXES']);
        self::assertTrue($params['SHOW_ACTION_PANEL']);
        self::assertTrue($params['ALLOW_INLINE_EDIT']);
        self::assertSame('Y', $params['AJAX_MODE']);
        self::assertArrayHasKey('ACTION_PANEL', $params);
    }

    public function testDefaultSettingsMatchLegacyBehavior(): void
    {
        $grid = new Grid('products', [ID::make('ID')]);

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertTrue($params['ALLOW_COLUMNS_SORT']);
        self::assertTrue($params['ALLOW_COLUMNS_RESIZE']);
        self::assertTrue($params['ALLOW_HORIZONTAL_SCROLL']);
        self::assertFalse($params['ALLOW_ROWS_SORT']);
        self::assertFalse($params['ALLOW_CONTEXT_MENU']);
        self::assertSame('Y', $params['AJAX_MODE']);
        self::assertArrayNotHasKey('SHOW_PAGESIZE', $params);
        self::assertArrayNotHasKey('STUB', $params);
        self::assertArrayNotHasKey('TILE_GRID_MODE', $params);
    }

    public function testItAppliesGridSettings(): void
    {
        $grid = new Grid('products', [ID::make('ID')]);
        $grid->setSettings(new GridSettings(
            allowColumnsSort: false,
            allowColumnsResize: false,
            allowRowsSort: true,
            allowContextMenu: true,
            pinHeader: true,
            stickedColumns: true,
            useAjax: false,
            pageSizes: [10, 20, 50],
            emptyMessage: 'Нет записей',
            aggregates: [['COLUMN_ID' => 'SUM', 'TYPE' => 'SUM']],
            tileMode: true,
            tileSize: 'm',
        ));

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertFalse($params['ALLOW_COLUMNS_SORT']);
        self::assertFalse($params['ALLOW_COLUMNS_RESIZE']);
        self::assertTrue($params['ALLOW_ROWS_SORT']);
        self::assertFalse($params['ALLOW_ROWS_SORT_INSTANT_SAVE']);
        self::assertTrue($params['ALLOW_CONTEXT_MENU']);
        self::assertTrue($params['ALLOW_PIN_HEADER']);
        self::assertTrue($params['ALLOW_STICKED_COLUMNS']);
        self::assertSame('N', $params['AJAX_MODE']);
        self::assertTrue($params['SHOW_PAGESIZE']);
        self::assertSame([
            ['NAME' => '10', 'VALUE' => 10],
            ['NAME' => '20', 'VALUE' => 20],
            ['NAME' => '50', 'VALUE' => 50],
        ], $params['PAGE_SIZES']);
        self::assertSame('Нет записей', $params['STUB']);
        self::assertSame([['COLUMN_ID' => 'SUM', 'TYPE' => 'SUM']], $params['AGGREGATE']);
        self::assertTrue($params['TILE_GRID_MODE']);
        self::assertSame('m', $params['TILE_SIZE']);
    }

    public function testItSupportsSelectAllRecordsCheckbox(): void
    {
        $adapter = new BitrixGridAdapter();

        // Case 1: No actions -> false
        $grid1 = new Grid('g1');
        self::assertFalse($adapter->componentParams($grid1)['SHOW_SELECT_ALL_RECORDS_CHECKBOX']);

        // Case 2: Action with allowRunByFilter -> true
        $grid2 = new Grid('g2');
        $grid2->setBulkActions([BulkAction::make('a')->allowRunByFilter()]);
        self::assertTrue($adapter->componentParams($grid2)['SHOW_SELECT_ALL_RECORDS_CHECKBOX']);

        // Case 3: Action without allowRunByFilter -> false
        $grid3 = new Grid('g3');
        $grid3->setBulkActions([BulkAction::make('a')]);
        self::assertFalse($adapter->componentParams($grid3)['SHOW_SELECT_ALL_RECORDS_CHECKBOX']);

        // Case 4: Explicit override -> true
        $grid4 = new Grid('g4');
        $grid4->showSelectAllRecordsCheckbox(true);
        self::assertTrue($adapter->componentParams($grid4)['SHOW_SELECT_ALL_RECORDS_CHECKBOX']);

        // Case 5: Dropdown with child run by filter -> true
        $grid5 = new Grid('g5');
        $grid5->setBulkActions([
            BulkActionDropdown::make('d')->items([
                BulkAction::make('a')->allowRunByFilter()
            ])
        ]);
        self::assertTrue($adapter->componentParams($grid5)['SHOW_SELECT_ALL_RECORDS_CHECKBOX']);
    }

    public function testItSupportsCollapsibleRowsAndColumnShift(): void
    {
        $grid = new class ('products', [
            ID::make('ID'),
            Text::make('Name', 'NAME')
        ]) extends Grid {
            public function hasCollapsibleRows(): bool
            {
                return true;
            }

            public function collapsibleShiftColumnId(): ?string
            {
                return 'NAME';
            }

            public function groupingAlign(): ?string
            {
                return 'right';
            }
        };

        $params = (new BitrixGridAdapter())->componentParams($grid);

        self::assertTrue($params['ENABLE_COLLAPSIBLE_ROWS'] ?? false);
        $nameColumn = null;
        foreach ($params['COLUMNS'] as $col) {
            if ($col['id'] === 'NAME') {
                $nameColumn = $col;
                break;
            }
        }

        self::assertNotNull($nameColumn);
        self::assertTrue($nameColumn['shift'] ?? false);
        self::assertSame('right', $nameColumn['align'] ?? null);
    }
}
