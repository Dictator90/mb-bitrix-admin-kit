<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Action\BulkAction;
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
}
