<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridActionPanelAdapter;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;

final class BitrixGridActionPanelAdapterTest extends TestCase
{
    public function testItBuildsInlineEditAndBulkActionItems(): void
    {
        $grid = new Grid('products', [Text::make('Name', 'NAME')->editable()]);
        $grid->setBulkActions([BulkAction::make('delete', 'Delete')->confirm('Really?')->danger()]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $items = $panel['GROUPS'][0]['ITEMS'];

        self::assertSame('edit_button', $items[0]['ID']);
        self::assertSame('delete', $items[1]['ID']);
        self::assertTrue($items[1]['ONCHANGE'][0]['CONFIRM']);
        self::assertSame('Really?', $items[1]['ONCHANGE'][0]['CONFIRM_MESSAGE']);
        self::assertSame('adm-btn-danger', $items[1]['CLASS']);
        self::assertStringContainsString("grid.reloadTable('POST',data)", $items[1]['ONCHANGE'][0]['DATA'][0]['JS']);
        self::assertStringContainsString('data.ID=ids', $items[1]['ONCHANGE'][0]['DATA'][0]['JS']);
    }
}
