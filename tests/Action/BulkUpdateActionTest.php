<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkUpdateAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BulkUpdateActionTest extends TestCase
{
    protected function tearDown(): void
    {
        ProductTable::reset();
    }

    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [
            ['ID' => 1, 'NAME' => 'One', 'ACTIVE' => 'N'],
            ['ID' => 2, 'NAME' => 'Two', 'ACTIVE' => 'N'],
        ];
    }

    public function testItUpdatesSelectedRows(): void
    {
        $result = BulkUpdateAction::make('activate')->update(['ACTIVE' => 'Y'])->execute(
            new BulkOperationContext(new ProductResource(), 'activate', [1, 2])
        );

        self::assertTrue($result->isSuccess());
        self::assertSame(2, $result->successCount);
        self::assertSame([1, 2], ProductTable::$updatedIds);
    }

    public function testFluentBulkActionCanRunBulkUpdate(): void
    {
        $result = BulkAction::make('activate')
            ->label('Активировать')
            ->update(['ACTIVE' => 'Y'])
            ->execute(new BulkOperationContext(new ProductResource(), 'activate', [1]));

        self::assertTrue($result->isSuccess());
        self::assertSame([1], ProductTable::$updatedIds);
    }

    public function testOneUpdateErrorDoesNotStopOtherRows(): void
    {
        ProductTable::$updateErrorsById = ['2' => ['Update failed']];

        $result = BulkUpdateAction::make('activate')->update(['ACTIVE' => 'Y'])->execute(
            new BulkOperationContext(new ProductResource(), 'activate', [1, 2])
        );

        self::assertFalse($result->isSuccess());
        self::assertSame(1, $result->successCount);
        self::assertSame(1, $result->failedCount);
        self::assertSame(['2' => ['Update failed']], $result->errors());
        self::assertSame([1], ProductTable::$updatedIds);
    }
}
