<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
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

        self::assertCount(1, $panel['GROUPS']);
        $items = $panel['GROUPS'][0]['ITEMS'];

        self::assertSame('edit_button', $items[0]['ID']);
        self::assertSame('delete', $items[1]['ID']);
        self::assertTrue($items[1]['ONCHANGE'][0]['CONFIRM']);
        self::assertSame('Really?', $items[1]['ONCHANGE'][0]['CONFIRM_MESSAGE']);
        self::assertSame('ui-btn-danger', $items[1]['CLASS']);
        self::assertStringContainsString("grid.reloadTable('POST',data)", $items[1]['ONCHANGE'][0]['DATA'][0]['JS']);
        self::assertStringContainsString('data.ID=ids', $items[1]['ONCHANGE'][0]['DATA'][0]['JS']);
    }

    public function testItSupportsForAllCheckbox(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([BulkAction::make('activate', 'Activate')->allowRunByFilter()]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $items = $panel['GROUPS'][0]['ITEMS'];

        self::assertSame('for_all_checkbox', $items[0]['ID']);
        self::assertSame('activate', $items[1]['ID']);
    }

    public function testItGroupsAndSortsActions(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('a', 'A')->sort(20),
            BulkAction::make('b', 'B')->sort(10),
            BulkAction::make('c', 'C')->group('other', 'Other Group')->sort(5),
            BulkAction::make('d', 'D')->group('other')->sort(1),
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);

        self::assertCount(2, $panel['GROUPS']);

        // Group 1: default (A, B sorted)
        $group1 = $panel['GROUPS'][0]['ITEMS'];
        self::assertSame('b', $group1[0]['ID']);
        self::assertSame('a', $group1[1]['ID']);

        // Group 2: other (C, D sorted)
        $group2 = $panel['GROUPS'][1]['ITEMS'];
        self::assertSame('d', $group2[0]['ID']);
        self::assertSame('c', $group2[1]['ID']);
    }

    public function testItIncludesIconsAndCustomClasses(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('test', 'Test')
                ->icon('ui-btn-icon-success')
                ->buttonClass('my-custom-class')
                ->title('Button Title')
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $item = $panel['GROUPS'][0]['ITEMS'][0];

        self::assertSame('test', $item['ID']);
        self::assertSame('Test', $item['TEXT']);
        self::assertSame('Button Title', $item['TITLE']);
        self::assertStringContainsString('my-custom-class', $item['CLASS']);
        self::assertStringContainsString('ui-btn-icon-success', $item['CLASS']);
    }

    public function testItSupportsCustomPanelItem(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('custom', 'Custom')
                ->panelItem(['TYPE' => 'TEXT', 'ID' => 'custom_text', 'VALUE' => 'hello'])
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $item = $panel['GROUPS'][0]['ITEMS'][0];

        self::assertSame('TEXT', $item['TYPE']);
        self::assertSame('custom_text', $item['ID']);
        self::assertSame('hello', $item['VALUE']);
    }

    public function testItSupportsCustomPanelItemClosure(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('custom', 'Custom')
                ->panelItem(fn (Grid $g) => ['TYPE' => 'TEXT', 'ID' => 'custom_' . $g->getId()])
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $item = $panel['GROUPS'][0]['ITEMS'][0];

        self::assertSame('custom_products', $item['ID']);
    }

    public function testItRendersDropdown(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkActionDropdown::make('activity', 'Activity')
                ->title('Activity title')
                ->items([
                    BulkAction::make('activate', 'Activate')->confirm('Are you sure?'),
                    BulkAction::make('deactivate', 'Deactivate'),
                ])
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $item = $panel['GROUPS'][0]['ITEMS'][0];

        self::assertSame('DROPDOWN', $item['TYPE']);
        self::assertSame('activity', $item['ID']);
        self::assertSame('ACTIVITY', $item['NAME']);
        self::assertSame('N', $item['MULTIPLE']);
        self::assertSame('Activity title', $item['TITLE']);
        self::assertArrayNotHasKey('TEXT', $item);
        self::assertCount(3, $item['ITEMS']);

        self::assertSame('Activity', $item['ITEMS'][0]['NAME']);
        self::assertSame('', $item['ITEMS'][0]['VALUE']);
        self::assertArrayNotHasKey('ONCHANGE', $item['ITEMS'][0]);

        self::assertSame('Activate', $item['ITEMS'][1]['NAME']);
        self::assertSame('activate', $item['ITEMS'][1]['VALUE']);
        self::assertTrue($item['ITEMS'][1]['ONCHANGE'][0]['CONFIRM']);
        self::assertStringContainsString("'activate'", $item['ITEMS'][1]['ONCHANGE'][0]['DATA'][0]['JS']);

        self::assertSame('Deactivate', $item['ITEMS'][2]['NAME']);
        self::assertSame('deactivate', $item['ITEMS'][2]['VALUE']);
    }


    public function testItSkipsInvisibleActionsAndEmptyDropdowns(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('hidden', 'Hidden')->canSee(false),
            BulkActionDropdown::make('empty', 'Empty')->items([
                BulkAction::make('hidden_child', 'Hidden child')->canSee(false),
            ]),
            BulkActionDropdown::make('visible_drop', 'Visible')->items([
                BulkAction::make('visible_child', 'Visible child'),
                BulkAction::make('hidden_child_2', 'Hidden child 2')->canSee(false),
            ]),
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $items = $panel['GROUPS'][0]['ITEMS'];

        self::assertCount(1, $items);
        self::assertSame('visible_drop', $items[0]['ID']);
        self::assertCount(2, $items[0]['ITEMS']);
        self::assertSame('Visible', $items[0]['ITEMS'][0]['NAME']);
        self::assertSame('visible_child', $items[0]['ITEMS'][1]['VALUE']);
    }

    public function testDropdownMultipleModeIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Multiple dropdown bulk actions are not supported yet.');

        BulkActionDropdown::make('activity')->multiple(true);
    }

    public function testItThrowsExceptionOnDuplicateIds(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkAction::make('test', 'Test 1'),
            BulkActionDropdown::make('drop', 'Drop')->items([
                BulkAction::make('test', 'Test 2'),
            ]),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Duplicate bulk action id [test]');

        (new BitrixGridActionPanelAdapter())->componentParams($grid);
    }
}
