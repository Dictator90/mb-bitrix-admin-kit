<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BulkActionMetadataTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'One']];
    }

    public function testItStoresMetadata(): void
    {
        $action = BulkAction::make('test', 'Test Label')
            ->group('my-group', 'My Label')
            ->sort(50)
            ->icon('my-icon')
            ->buttonClass('my-class')
            ->title('My Title')
            ->panelType('DROPDOWN');

        self::assertSame('my-group', $action->getGroup());
        self::assertSame('My Label', $action->getGroupLabel());
        self::assertSame(50, $action->getSort());
        self::assertSame('my-icon', $action->getIcon());
        self::assertSame('my-class', $action->getButtonClass());
        self::assertSame('My Title', $action->getTitle());
        self::assertSame('DROPDOWN', $action->getPanelType());
        self::assertSame('runBulkAction', $action->getClientHandler());

        $action->clientHandler('customHandler');
        self::assertSame('customHandler', $action->getClientHandler());
    }

    public function testDeleteActionHasDefaults(): void
    {
        $action = BulkAction::delete();

        self::assertSame('danger', $action->getGroup());
        self::assertSame('Deletion', $action->getGroupLabel());
        self::assertSame('ui-btn-icon-remove', $action->getIcon());
        self::assertSame(100, $action->getSort());
        self::assertTrue($action->isDanger());
    }

    public function testItExecutesUpdate(): void
    {
        $action = BulkAction::make('test')->update(['NAME' => 'Updated']);
        $resource = new ProductResource();
        $context = new BulkOperationContext($resource, $action, [1]);

        $result = $action->execute($context);

        self::assertTrue($result->isSuccess());
        self::assertSame([1], ProductTable::$updatedIds);
    }
}
