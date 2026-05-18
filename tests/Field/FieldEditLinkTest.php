<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;
use MB\Bitrix\AdminKit\Page\ResourceBackedIndexPageDefinition;
use MB\Bitrix\AdminKit\Tests\Fixtures\FakeQueryResult;
use MB\Bitrix\AdminKit\Tests\Fixtures\GroupedRowsBuilderGroupResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\GroupedRowsBuilderGroupTable;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class FieldEditLinkTest extends TestCase
{
    protected function setUp(): void
    {
        if (class_exists(GroupedRowsBuilderGroupTable::class)) {
            GroupedRowsBuilderGroupTable::$rows = [
                ['ID' => 1, 'NAME' => 'Root', 'PARENT_ID' => null],
            ];
        }
    }

    public function testAsEditLinkRendersItemEditLink(): void
    {
        $resource = new ProductResource();
        $rows = (new RowAssembler(
            [Text::make('Name', 'NAME')->asEditLink()],
            [],
            '/admin.php?page=products',
            'ID',
            $resource,
            GridContext::make($resource),
            new ResourceBackedIndexPageDefinition($resource),
        ))->buildRows(new FakeQueryResult([['ID' => 5, 'NAME' => 'Cookie']]));

        self::assertSame('<a href="/admin.php?page=products&amp;action=edit&amp;id=5">Cookie</a>', $rows[0]['columns']['NAME']);
    }

    public function testLinkToEditAliasAndSidePanel(): void
    {
        $resource = new class () extends ProductResource {
            public function editInSidePanel(): bool
            {
                return true;
            }
        };
        $rows = (new RowAssembler(
            [Text::make('Name', 'NAME')->linkToEdit()],
            [],
            '/admin.php?page=products',
            'ID',
            $resource,
            GridContext::make($resource),
            new ResourceBackedIndexPageDefinition($resource),
        ))->buildRows(new FakeQueryResult([['ID' => 5, 'NAME' => 'Cookie']]));

        self::assertStringContainsString('BX.SidePanel.Instance.open', $rows[0]['columns']['NAME']);
    }

    public function testGroupRowsIgnoreFieldEditLinkAndUseGroupResource(): void
    {
        $resource = new class () extends ProductResource {
            public function indexGrouping(): ?IndexGrouping
            {
                return IndexGrouping::make()
                    ->resource(GroupedRowsBuilderGroupResource::class)
                    ->foreignKey('GROUP_ID')
                    ->label('NAME')
                    ->labelColumn('NAME');
            }
        };
        $definition = new ResourceBackedIndexPageDefinition($resource);

        $rows = (new RowAssembler(
            [Text::make('Name', 'NAME')->asEditLink()],
            [],
            '/admin.php?page=products',
            'ID',
            $resource,
            GridContext::make($resource),
            $definition,
        ))->buildRows(new FakeQueryResult([['ID' => 5, 'NAME' => 'Cookie', 'GROUP_ID' => 1]]));

        self::assertSame('group:1', $rows[0]['id']);
        self::assertStringContainsString('page=groups', $rows[0]['columns']['NAME']);
        self::assertSame('item:5', $rows[1]['id']);
    }
}
