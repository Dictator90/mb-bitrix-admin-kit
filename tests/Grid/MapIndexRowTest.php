<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;
use MB\Bitrix\AdminKit\Tests\Fixtures\FakeQueryResult;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class MapIndexRowTest extends TestCase
{
    public function testItAppliesAfterRowsAndMapRowHooks(): void
    {
        $resource = new class extends ProductResource {
            public function afterIndexRows(array $rows, GridContext $context): array
            {
                $rows[0]['NAME'] = 'After';

                return $rows;
            }

            public function mapIndexRow(array $row, GridContext $context): array
            {
                $row['NAME'] .= ' Map';

                return $row;
            }
        };
        $context = GridContext::make($resource);

        $rows = (new RowAssembler([Text::make('Name', 'NAME')], [], '', 'ID', $resource, $context))->buildRows(
            new FakeQueryResult([['ID' => 1, 'NAME' => 'Before']])
        );

        self::assertSame('After Map', $rows[0]['data']['NAME']);
        self::assertSame('After Map', $rows[0]['columns']['NAME']);
    }
}
