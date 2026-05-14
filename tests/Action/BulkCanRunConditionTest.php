<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkUpdateAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BulkCanRunConditionTest extends TestCase
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
            ['ID' => 2, 'NAME' => 'Two', 'ACTIVE' => 'Y'],
        ];
    }

    public function testItAcceptsShortCanRunCondition(): void
    {
        $result = BulkUpdateAction::make('activate')
            ->canRun('ACTIVE', '=', 'N')
            ->update(['ACTIVE' => 'Y'])
            ->execute(new BulkOperationContext(new ProductResource(), 'activate', [1, 2]));

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $result->successCount);
        self::assertSame(['2' => 'Bulk action is not allowed for this item.'], $result->skippedIds);
    }

    public function testItAcceptsClosureAndConditionTree(): void
    {
        $closure = BulkUpdateAction::make('activate')
            ->canRun(fn (array $context): bool => ($context['item']['ID'] ?? null) === 1)
            ->update(['ACTIVE' => 'Y'])
            ->execute(new BulkOperationContext(new ProductResource(), 'activate', [1]));

        $tree = BulkUpdateAction::make('activate')
            ->canRun(AdminCondition::tree()->where('ACTIVE', '=', 'N'))
            ->update(['ACTIVE' => 'Y'])
            ->execute(new BulkOperationContext(new ProductResource(), 'activate', [1]));

        self::assertTrue($closure->isSuccess());
        self::assertTrue($tree->isSuccess());
    }
}
