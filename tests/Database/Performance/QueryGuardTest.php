<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class QueryGuardTest extends TestCase
{
    public function testRejectsRunByFilterWithoutExplicitOptIn(): void
    {
        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('export'),
            selectedIds: [],
            filter: ['ACTIVE' => 'Y'],
            forAll: true,
        ));

        self::assertNotEmpty($errors);
    }

    public function testAllowsSelectedIds(): void
    {
        $errors = (new QueryGuard())->validateBulkOperation(new BulkOperationContext(
            resource: new ProductResource(),
            action: BulkAction::make('export'),
            selectedIds: [1],
        ));

        self::assertSame([], $errors);
    }
}
