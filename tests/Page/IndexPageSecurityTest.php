<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class IndexPageSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = [];
        $_SESSION = [];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = '';
        $GLOBALS['last_redirect'] = null;
    }

    public function testPostBulkWithoutSessidDoesNotExecuteBulkAction(): void
    {
        $resource = new BulkTrackingResource();
        $page = new IndexPage($resource);

        $gridId = $resource->getGridId();
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [];
        $_POST['action_button_' . $gridId] = 'track_bulk';

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertFalse(BulkTrackingResource::$bulkExecuted);
        self::assertSame('/bitrix/admin/test.php', $GLOBALS['last_redirect']);
    }

    public function testPostInlineEditWithoutSessidDoesNotSaveRows(): void
    {
        $resource = new InlineTrackingResource();
        $page = new IndexPage($resource);
        $gridId = $resource->getGridId();

        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $_POST['action_button_' . $gridId] = 'edit';
        $_POST['FIELDS'] = ['1' => ['NAME' => 'Changed']];

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame(0, InlineTrackingResource::$updateCalls);
    }

    public function testDeleteSupportsStringPrimaryKeyWithoutIntCast(): void
    {
        $resource = new StringIdDeleteResource();
        $page = new IndexPage($resource);

        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = ['action' => 'delete', 'id' => 'sku-42'];

        ob_start();
        try {
            $page->render();
        } catch (\Throwable) {
            // LocalRedirect may not stop execution in all environments.
        }
        ob_end_clean();

        self::assertSame(['sku-42'], StringIdDeleteResource::$deletedIds);
        self::assertSame('sku-42', StringIdDeleteResource::$lastFindId);
    }

    public function testCanViewFalseDoesNotLoadGridAndShowsError(): void
    {
        $resource = new DeniedIndexViewResource();
        $page = new class ($resource) extends IndexPage {
            public static bool $loadDataCalled = false;

            protected function loadData(\MB\Bitrix\AdminKit\Grid\Grid $grid): void
            {
                self::$loadDataCalled = true;
                parent::loadData($grid);
            }
        };
        $page::class::$loadDataCalled = false;

        ob_start();
        try {
            $page->render();
        } finally {
            $html = (string)ob_get_clean();
        }

        self::assertFalse($page::class::$loadDataCalled);
        self::assertStringContainsString('Недостаточно прав для просмотра раздела.', $html);
        self::assertStringNotContainsString('main.ui.grid', $html);
    }

}

final class BulkTrackingResource extends ProductResource
{
    public static bool $bulkExecuted = false;

    public function bulkActions(): iterable
    {
        return [
            BulkAction::make('track_bulk', 'Track')->handle(static function (): void {
                self::$bulkExecuted = true;
            }),
        ];
    }
}

final class InlineTrackingResource extends ProductResource
{
    public static int $updateCalls = 0;

    public function updateItemResult(mixed $id, \MB\Bitrix\AdminKit\Form\FormData|array $data, ?\MB\Bitrix\AdminKit\Database\DbOperationContext $context = null): \MB\Bitrix\AdminKit\Database\DbResult
    {
        self::$updateCalls++;

        return parent::updateItemResult($id, $data, $context);
    }
}

final class StringIdDeleteResource extends ProductResource
{
    /** @var list<mixed> */
    public static array $deletedIds = [];

    public static mixed $lastFindId = null;

    public function findItem(mixed $id): ?array
    {
        self::$lastFindId = $id;

        return ['SKU' => (string)$id, 'NAME' => 'Item'];
    }

    public function delete(int|string $id): bool
    {
        self::$deletedIds[] = $id;

        return true;
    }

    public function getPrimaryKey(): string
    {
        return 'SKU';
    }
}

final class DeniedIndexViewResource extends ProductResource
{
    public function canView(?\MB\Bitrix\AdminKit\Security\PermissionContext $context = null): bool
    {
        return false;
    }
}
