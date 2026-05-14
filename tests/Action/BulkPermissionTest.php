<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkUpdateAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BulkPermissionTest extends TestCase
{
    protected function tearDown(): void
    {
        ProductTable::reset();
    }

    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [
            ['ID' => 1, 'NAME' => 'One'],
            ['ID' => 2, 'NAME' => 'Two'],
        ];
    }

    public function testItSkipsRowsWithoutUpdatePermission(): void
    {
        $resource = new class () extends ProductResource {
            public function canUpdate(PermissionContext|\MB\Bitrix\AdminKit\Support\DataWrapper|null $context = null): bool
            {
                return (int)($context instanceof PermissionContext ? ($context->item['ID'] ?? 0) : 0) !== 2;
            }
        };

        $result = BulkUpdateAction::make('activate')->update(['ACTIVE' => 'Y'])->execute(
            new BulkOperationContext($resource, 'activate', [1, 2])
        );

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $result->successCount);
        self::assertSame(['2' => 'Update permission denied.'], $result->skippedIds);
        self::assertSame([1], ProductTable::$updatedIds);
    }
}
