<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;
use MB\Bitrix\AdminKit\Tests\Fixtures\FakeQueryResult;
use PHPUnit\Framework\TestCase;

final class ComputedColumnTest extends TestCase
{
    public function testItComputesGridColumnAfterDatabaseFetch(): void
    {
        $field = Text::make('User', 'USER_FULL_NAME')->computed(
            fn(array $row): string => trim(($row['USER_NAME'] ?? '') . ' ' . ($row['USER_LAST_NAME'] ?? ''))
        );

        $rows = (new RowAssembler([$field]))->buildRows(new FakeQueryResult([
            ['ID' => 1, 'USER_NAME' => 'Ada', 'USER_LAST_NAME' => 'Lovelace'],
        ]));

        self::assertSame('Ada Lovelace', $rows[0]['data']['USER_FULL_NAME']);
        self::assertSame('Ada Lovelace', $rows[0]['columns']['USER_FULL_NAME']);
    }
}
