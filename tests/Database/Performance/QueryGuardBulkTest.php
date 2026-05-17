<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class QueryGuardBulkTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testSelectedIdsModeIsAllowed(): void
    {
        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('a'),
            selectedIds: [1],
            forAll: false,
        ));

        self::assertSame([], $errors);
    }

    public function testForAllRequiresRunByFilterOptIn(): void
    {
        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('a'),
            selectedIds: [],
            filter: ['ACTIVE' => 'Y'],
            forAll: true,
        ));

        self::assertSame(['Run by filter is not explicitly allowed for this bulk action.'], $errors);
    }

    public function testForAllEmptyFilterRequiresExplicitOptIn(): void
    {
        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('a')->allowRunByFilter(),
            selectedIds: [],
            filter: [],
            forAll: true,
        ));

        self::assertSame(['Running bulk action for all records without filter is not allowed.'], $errors);

        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('a')->allowRunByFilter()->allowRunWithoutFilter(),
            selectedIds: [],
            filter: [],
            forAll: true,
        ));

        self::assertSame([], $errors);
    }

    public function testForAllCountGuard(): void
    {
        ProductTable::$rows = [];
        for ($i = 1; $i <= 3; $i++) {
            ProductTable::$rows[] = ['ID' => $i, 'ACTIVE' => 'Y'];
        }

        $resource = new class () extends ProductResource {
            public function maxBulkRows(): int
            {
                return 2;
            }
        };

        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: $resource,
            action: BulkAction::make('a')->allowRunByFilter(),
            filter: ['ACTIVE' => 'Y'],
            forAll: true,
        ));

        self::assertSame(['Bulk operation affects too many rows: 3. Maximum allowed: 2.'], $errors);
    }
}
