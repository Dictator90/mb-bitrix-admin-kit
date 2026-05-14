<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkUpdateAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BulkChunkProcessingTest extends TestCase
{
    protected function tearDown(): void
    {
        ProductTable::reset();
    }

    public function testItProcessesAllIdsWhenChunkSizeIsSmall(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [
            ['ID' => 1, 'NAME' => 'One'],
            ['ID' => 2, 'NAME' => 'Two'],
            ['ID' => 3, 'NAME' => 'Three'],
        ];

        $resource = new class extends ProductResource {
            public function bulkChunkSize(): int { return 2; }
        };

        $result = BulkUpdateAction::make('activate')->update(['ACTIVE' => 'Y'])->execute(
            new BulkOperationContext($resource, 'activate', [1, 2, 3])
        );

        self::assertTrue($result->isSuccess());
        self::assertSame(3, $result->successCount);
        self::assertSame([1, 2, 3], ProductTable::$updatedIds);
    }
}
