<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkUpdateAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class EmptySelectedIdsTest extends TestCase
{
    public function testBulkActionDoesNotRunWithoutSelectedIds(): void
    {
        $result = BulkUpdateAction::make('activate')->update(['ACTIVE' => 'Y'])->execute(
            new BulkOperationContext(new ProductResource(), 'activate')
        );

        self::assertFalse($result->isSuccess());
        self::assertSame(['_bulk' => ['No items selected.']], $result->errors());
    }
}
