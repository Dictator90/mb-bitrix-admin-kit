<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class BulkActionCanRunTest extends TestCase
{
    public function testCustomHandlerIsBlockedByActionLevelCanRun(): void
    {
        $action = BulkAction::make('custom')
            ->canRun(false)
            ->handle(static fn (): BulkResult => BulkResult::success([1]));

        $result = $action->execute(new BulkOperationContext(new ProductResource(), $action, [1]));

        self::assertFalse($result->isSuccess());
        self::assertSame(['Bulk action is not allowed.'], $result->errors()['_bulk']);
    }
}
