<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class BulkCallbackActionTest extends TestCase
{
    public function testItRunsCallbackWithSelectedIdsAndContext(): void
    {
        $action = BulkAction::make('recalculate')
            ->label('Пересчитать')
            ->handle(function (array $ids, BulkOperationContext $context): BulkResult {
                self::assertSame([1, 2], $ids);
                self::assertSame('recalculate', $context->action->getId());

                return BulkResult::success($ids);
            });

        $result = $action->execute(new BulkOperationContext(new ProductResource(), $action, [1, 2]));

        self::assertTrue($result->isSuccess());
        self::assertSame(2, $result->successCount);
    }
}
