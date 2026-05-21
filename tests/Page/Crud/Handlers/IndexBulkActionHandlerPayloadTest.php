<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexBulkActionHandler;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use PHPUnit\Framework\TestCase;

final class IndexBulkActionHandlerPayloadTest extends TestCase
{
    public function testHandleReturnsExtendedPayload(): void
    {
        $resource = new class () extends DataManagerResource {
            public static function getId(): string
            {
                return 'test';
            }
            public function dataManagerClass(): string
            {
                return 'SomeTable';
            }
            public function getPrimaryKey(): string
            {
                return 'ID';
            }
            public function bulkActions(): iterable
            {
                yield BulkAction::make('test', 'Test')->executeUsing(function () {
                    $res = new BulkResult();
                    $res->addSuccess(1);
                    $res->addError(2, 'Failed');
                    return $res;
                });
            }
        };

        $page = new class ($resource) extends IndexPage {};
        $_POST['ID'] = [1, 2];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = $_POST;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $handler = new IndexBulkActionHandler();

        $payload = $handler->handle($page, 'test');

        self::assertArrayHasKey('errors', $payload);
        self::assertSame('error', $payload['status']);
        self::assertSame(1, $payload['affected']);
        self::assertArrayHasKey('skipped', $payload);
        self::assertArrayHasKey('summary', $payload);
        self::assertSame(['2' => ['Failed']], $payload['errors']);
        self::assertSame(2, $payload['summary']['total']);
        self::assertContains($payload, $_SESSION['MB_ADMIN_KIT_BULK_RESULT']);
    }
}
