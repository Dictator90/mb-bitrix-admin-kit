<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Tests\Fixtures\FakeQueryResult;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class FullWidthGroupRowsTest extends TestCase
{
    public function testRowAssemblerBuildsCustomGroupRowWhenFullWidthIsEnabled(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(ProductResource::class)
            ->foreignKey('GROUP_ID')
            ->ownerKey('ID')
            ->label('NAME')
            ->labelColumn('NAME')
            ->fullWidth(true);

        $dataRows = [
            [
                '__ROW_TYPE' => 'group',
                '__GROUP_ID' => 1,
                '__GROUP_RESOURCE' => ProductResource::class,
                '__GROUP_DATA' => ['ID' => 1, 'NAME' => 'Root'],
                '__GRID_ROW_ID' => 'group:1',
                'NAME' => 'Root',
                '__adminkit_grid_row' => [
                    'has_child' => true,
                    'expand' => false,
                ],
            ],
            [
                '__ROW_TYPE' => 'item',
                '__REAL_ID' => 10,
                '__GRID_ROW_ID' => 'item:10',
                'NAME' => 'Cookie',
                '__adminkit_grid_row' => [
                    'shift' => true,
                    'depth' => 1,
                    'parent_id' => 'group:1',
                    'group_id' => '1',
                    'parent_group_id' => '1',
                ],
            ],
        ];

        $page = new IndexPageDefinition([
            'fields' => static fn (): array => [Text::make('Name', 'NAME')],
            'filters' => static fn (): array => [],
            'rowActions' => static fn (): array => [],
            'bulkActions' => static fn (): array => [],
            'defaultSort' => static fn (): array => [],
            'defaultFilter' => static fn (): array => [],
            'defaultSelect' => static fn (): array => [],
            'runtimeFields' => static fn (): array => [],
            'indexSelect' => static fn (): array => [],
            'indexFilter' => static fn (): array => [],
            'indexOrder' => static fn (): array => [],
            'indexRuntime' => static fn (): array => [],
            'beforeIndexQueryParams' => static fn (array $params): array => $params,
            'afterIndexRows' => static fn (array $rows): array => $rows,
            'mapIndexRow' => static fn (array $row): array => $row,
            'modifyIndexParams' => static fn (array $params): array => $params,
            'grouping' => static fn (): IndexGrouping => $grouping,
        ]);

        $rows = (new RowAssembler(
            [Text::make('Name', 'NAME')],
            [],
            '',
            'ID',
            new ProductResource(),
            null,
            $page,
        ))->buildRows(new FakeQueryResult($dataRows));

        self::assertSame('left', $rows[0]['align'] ?? null);
        self::assertSame('left', $rows[0]['attrs']['data-align'] ?? null);
        self::assertSame('1', $rows[0]['group_id'] ?? null);
        self::assertSame('1', $rows[0]['attrs']['data-group-id'] ?? null);
        self::assertArrayNotHasKey('shift', $rows[0]);
        self::assertSame('1', $rows[1]['group_id'] ?? null);
        self::assertSame('1', $rows[1]['parent_group_id'] ?? null);
    }
}
