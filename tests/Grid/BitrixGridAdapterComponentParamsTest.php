<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Grid;
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
