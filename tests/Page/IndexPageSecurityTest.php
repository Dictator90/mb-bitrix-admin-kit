<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;

final class IndexPageSecurityTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setGetRequest();
        $_SESSION = [];
        $GLOBALS['last_redirect'] = null;
        $GLOBALS['APPLICATION'] = new class () {
            public function SetTitle(string $title): void
            {
            }

            public function IncludeComponent(string $name, string $template, array $params): void
            {
            }

            public function StoreCookies(): void
            {
            }
        };
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testPostBulkWithoutSessidDoesNotExecuteBulkAction(): void
    {
        $resource = new BulkTrackingResource();
        $gridId = $resource->getGridId();
        $this->setPostRequest([
            'action_button_' . $gridId => 'track_bulk',
            'sessid' => 'invalid',
        ]);
        $page = new class ($resource) extends IndexPage {
            public function redirect(string $url): void
            {
                $GLOBALS['last_redirect'] = $url;
            }
        };

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertFalse(BulkTrackingResource::$bulkExecuted);
        self::assertArrayHasKey($gridId, $_SESSION['MB_ADMIN_KIT_BULK_RESULT'] ?? []);
        self::assertIsString($GLOBALS['last_redirect']);
    }

    public function testPostInlineEditWithoutSessidDoesNotSaveRows(): void
    {
        $resource = new InlineTrackingResource();
        $gridId = $resource->getGridId();

        $this->setPostRequest([
            'action_button_' . $gridId => 'edit',
            'FIELDS' => ['1' => ['NAME' => 'Changed']],
            'sessid' => 'invalid',
        ]);
        $page = new class ($resource) extends IndexPage {
            public function redirect(string $url): void
            {
                $GLOBALS['last_redirect'] = $url;
            }
        };

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame(0, InlineTrackingResource::$updateCalls);
    }

    public function testDeleteSupportsStringPrimaryKeyWithoutIntCast(): void
    {
        $resource = new StringIdDeleteResource();
        $this->setGetRequest(['action' => 'delete', 'id' => 'sku-42']);
        $page = new class ($resource) extends IndexPage {
            public function redirect(string $url): void
            {
                $GLOBALS['last_redirect'] = $url;
            }
        };

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame(['sku-42'], StringIdDeleteResource::$deletedIds);
        self::assertSame('sku-42', StringIdDeleteResource::$lastFindId);
    }

    public function testCanViewFalseBlocksExportWithoutBuildingGrid(): void
    {
        $resource = new DeniedIndexViewResource();
        $page = new class ($resource) extends IndexPage {
            public static bool $exportCalled = false;

            public static bool $buildGridCalled = false;

            public static bool $loadDataCalled = false;

            protected function handleExportAction(?array $selectedIdsOverride = null): void
            {
                self::$exportCalled = true;
            }

            public function buildGrid(): \MB\Bitrix\AdminKit\Grid\Grid
            {
                self::$buildGridCalled = true;

                return parent::buildGrid();
            }

            protected function loadData(\MB\Bitrix\AdminKit\Grid\Grid $grid): void
            {
                self::$loadDataCalled = true;
            }
        };
        $page::class::$exportCalled = false;
        $page::class::$buildGridCalled = false;
        $page::class::$loadDataCalled = false;

        $this->setGetRequest(['action' => 'export']);

        ob_start();
        try {
            $page->render();
        } finally {
            $html = (string)ob_get_clean();
        }

        self::assertFalse($page::class::$exportCalled);
        self::assertFalse($page::class::$buildGridCalled);
        self::assertFalse($page::class::$loadDataCalled);
        self::assertTrue(str_contains($html, 'Недостаточно прав для просмотра раздела.') || str_contains($html, 'Insufficient permissions to view this section.'));
    }

    public function testInlineEditPreservesStringId(): void
    {
        $resource = new InlineIdTrackingResource();
        $gridId = $resource->getGridId();

        $this->setPostRequest([
            'action_button_' . $gridId => 'edit',
            'FIELDS' => ['sku-42' => ['NAME' => 'Changed']],
        ]);
        $page = new IndexPage($resource);

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('sku-42', InlineIdTrackingResource::$lastInlineId);
        self::assertSame('sku-42', InlineIdTrackingResource::$lastFindId);
    }

    public function testInlineEditPreservesIntLikeId(): void
    {
        $resource = new InlineIdTrackingResource();
        $gridId = $resource->getGridId();

        $this->setPostRequest([
            'action_button_' . $gridId => 'edit',
            'FIELDS' => [7 => ['NAME' => 'Changed']],
        ]);
        $page = new IndexPage($resource);

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame(7, InlineIdTrackingResource::$lastInlineId);
        self::assertSame(7, InlineIdTrackingResource::$lastFindId);
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
        self::assertTrue(str_contains($html, 'Недостаточно прав для просмотра раздела.') || str_contains($html, 'Insufficient permissions to view this section.'));
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

final class InlineIdTrackingResource extends ProductResource
{
    public static mixed $lastInlineId = null;

    public static mixed $lastFindId = null;

    public function findItem(mixed $id): ?array
    {
        self::$lastFindId = $id;

        return ['ID' => $id, 'NAME' => 'Item'];
    }

    public function updateItemResult(mixed $id, \MB\Bitrix\AdminKit\Form\FormData|array $data, ?\MB\Bitrix\AdminKit\Database\DbOperationContext $context = null): \MB\Bitrix\AdminKit\Database\DbResult
    {
        self::$lastInlineId = $context?->itemId ?? $id;

        return parent::updateItemResult($id, $data, $context);
    }
}
