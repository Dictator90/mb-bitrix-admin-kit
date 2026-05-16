<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ImportRemovedFromIndexPageTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testIndexPageSourceDoesNotReferenceImportNamespace(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 2) . '/src/Page/IndexPage.php',
        );

        self::assertStringNotContainsString('MB\\Bitrix\\AdminKit\\Import\\', $source);
        self::assertStringNotContainsString('MB_ADMIN_KIT_IMPORT', $source);
        self::assertStringNotContainsString('handleImportAction', $source);
        self::assertStringNotContainsString('action=import', $source);
    }

    public function testActionImportIsNotHandledAsDedicatedFlow(): void
    {
        $page = new class (new ProductResource()) extends IndexPage {
            public static bool $loadDataCalled = false;

            protected function loadData(\MB\Bitrix\AdminKit\Grid\Grid $grid): void
            {
                self::$loadDataCalled = true;
            }
        };
        $page::class::$loadDataCalled = false;

        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = ['action' => 'import'];

        $page->render();

        self::assertTrue($page::class::$loadDataCalled);
    }

    public function testRenderDoesNotWriteImportSessionStorage(): void
    {
        unset($_SESSION['MB_ADMIN_KIT_IMPORT']);

        $page = new class (new ProductResource()) extends IndexPage {
            protected function loadData(\MB\Bitrix\AdminKit\Grid\Grid $grid): void
            {
            }
        };

        $page->render();

        self::assertArrayNotHasKey('MB_ADMIN_KIT_IMPORT', $_SESSION);
    }
}
