<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexBulkActionHandler;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class IndexBulkActionHandlerDropdownTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];
        ProductTable::reset();
        ProductTable::$rows = [
            ['ID' => 1, 'NAME' => 'One', 'ACTIVE' => 'N'],
            ['ID' => 2, 'NAME' => 'Two', 'ACTIVE' => 'N'],
        ];
    }

    public function testItFindsAndExecutesChildAction(): void
    {
        $resource = new ProductResource();
        $page = new class ($resource) extends IndexPage {
            public function bulkActions(): iterable
            {
                return [
                    BulkActionDropdown::make('activity')->items([
                        BulkAction::make('activate', 'Activate')->update(['ACTIVE' => 'Y'])
                    ])
                ];
            }
        };

        $_POST['ID'] = [1];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = $_POST;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;

        $handler = new IndexBulkActionHandler();
        $result = $handler->handle($page, 'activate');

        self::assertTrue($result['success']);
        self::assertTrue(
            str_contains($result['message'], 'Успешно: 1') || str_contains($result['message'], 'Success: 1')
        );
        self::assertSame([1], ProductTable::$updatedIds);
    }

    public function testItSupportsRunByFilterForChildAction(): void
    {
        $resource = new ProductResource();
        $page = new class ($resource) extends IndexPage {
            public function bulkActions(): iterable
            {
                return [
                    BulkActionDropdown::make('activity')->items([
                        BulkAction::make('activate')->allowRunByFilter()->allowRunWithoutFilter()->update(['ACTIVE' => 'Y'])
                    ])
                ];
            }
        };

        $_POST['action_all_rows_' . $resource->getGridId()] = 'Y';
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = $_POST;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;

        $handler = new IndexBulkActionHandler();
        $result = $handler->handle($page, 'activate');

        self::assertTrue($result['success']);
        self::assertSame([1, 2], ProductTable::$updatedIds);
    }
}
