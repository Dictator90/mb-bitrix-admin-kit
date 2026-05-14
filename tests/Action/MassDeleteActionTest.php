<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class MassDeleteActionTest extends TestCase
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

    public function testItDeletesSelectedRowsAndCallsMassHooks(): void
    {
        $resource = new class () extends ProductResource {
            public array $hooks = [];
            public function beforeMassDelete(array $ids, DbOperationContext $context): void
            {
                $this->hooks[] = ['before', $ids, $context->operation];
            }
            public function afterMassDelete(array $ids, DbOperationContext $context): void
            {
                $this->hooks[] = ['after', $ids, $context->operation];
            }
        };

        $result = (new MassDeleteAction())->execute(new BulkOperationContext($resource, 'delete', [1, 2]));

        self::assertTrue($result->isSuccess());
        self::assertSame([1, 2], ProductTable::$deletedIds);
        self::assertSame([
            ['before', [1, 2], 'massDelete'],
            ['after', [1, 2], 'massDelete'],
        ], $resource->hooks);
    }
}
