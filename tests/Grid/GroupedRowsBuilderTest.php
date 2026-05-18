<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Grouping\GroupedRowsBuilder;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Tests\Fixtures\GroupedRowsBuilderGroupResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\GroupedRowsBuilderGroupTable;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GroupedRowsBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        GroupedRowsBuilderGroupTable::$rows = [
            ['ID' => 1, 'NAME' => 'Root', 'PARENT_ID' => null],
            ['ID' => 2, 'NAME' => 'Child', 'PARENT_ID' => 1],
        ];
        GroupedRowsBuilderGroupTable::$lastParams = [];
    }

    public function testItBuildsGroupAndItemRows(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(GroupedRowsBuilderGroupResource::class)
            ->foreignKey('GROUP_ID')
            ->ownerKey('ID')
            ->label('NAME')
            ->labelColumn('NAME')
            ->expand(false);

        $rows = (new GroupedRowsBuilder())->build(
            [['ID' => 10, 'NAME' => 'Cookie', 'SORT' => 100, 'GROUP_ID' => 1]],
            new ProductResource(),
            $grouping,
            GridContext::make(new ProductResource()),
            null,
            [Text::make('Name', 'NAME'), Text::make('Sort', 'SORT')],
        );

        self::assertSame('group', $rows[0]['__ROW_TYPE']);
        self::assertSame('group:1', $rows[0]['__GRID_ROW_ID']);
        self::assertSame('Root', $rows[0]['NAME']);
        self::assertNull($rows[0]['SORT']);
        self::assertTrue($rows[0]['__adminkit_grid_row']['has_child']);
        self::assertSame('item:10', $rows[1]['__GRID_ROW_ID']);
        self::assertSame('group:1', $rows[1]['__adminkit_grid_row']['parent_id']);
        self::assertSame(1, $rows[1]['__adminkit_grid_row']['depth']);
    }

    public function testItBuildsNestedGroupsAndLoadsParents(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(GroupedRowsBuilderGroupResource::class)
            ->foreignKey('GROUP_ID')
            ->ownerKey('ID')
            ->parentKey('PARENT_ID')
            ->label('NAME')
            ->labelColumn('NAME');

        $rows = (new GroupedRowsBuilder())->build(
            [['ID' => 10, 'NAME' => 'Cookie', 'GROUP_ID' => 2]],
            new ProductResource(),
            $grouping,
            GridContext::make(new ProductResource()),
            null,
            [Text::make('Name', 'NAME')],
        );

        self::assertSame(['group:1', 'group:2', 'item:10'], array_column($rows, '__GRID_ROW_ID'));
        self::assertArrayNotHasKey('depth', $rows[0]['__adminkit_grid_row']);
        self::assertSame(1, $rows[1]['__adminkit_grid_row']['depth']);
        self::assertSame(2, $rows[2]['__adminkit_grid_row']['depth']);
    }

    public function testItAddsUngroupedGroupWhenEnabled(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(GroupedRowsBuilderGroupResource::class)
            ->foreignKey('GROUP_ID')
            ->label('NAME')
            ->labelColumn('NAME')
            ->ungroupedLabel('Без группы');

        $rows = (new GroupedRowsBuilder())->build(
            [['ID' => 10, 'NAME' => 'Cookie']],
            new ProductResource(),
            $grouping,
            GridContext::make(new ProductResource()),
            null,
            [Text::make('Name', 'NAME')],
        );

        self::assertSame('group:__ungrouped', $rows[0]['__GRID_ROW_ID']);
        self::assertSame('Без группы', $rows[0]['NAME']);
        self::assertSame('item:10', $rows[1]['__GRID_ROW_ID']);
    }

    public function testItLeavesUngroupedItemsNormalWhenDisabled(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(GroupedRowsBuilderGroupResource::class)
            ->foreignKey('GROUP_ID')
            ->showUngrouped(false);

        $rows = (new GroupedRowsBuilder())->build(
            [['ID' => 10, 'NAME' => 'Cookie']],
            new ProductResource(),
            $grouping,
            GridContext::make(new ProductResource()),
        );

        self::assertSame('item', $rows[0]['__ROW_TYPE']);
        self::assertArrayNotHasKey('__adminkit_grid_row', $rows[0]);
    }
}
