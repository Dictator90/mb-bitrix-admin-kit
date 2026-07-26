<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\DetailPage as CrudDetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage as CrudFormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage as CrudIndexPage;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Page\Page;
use MB\Bitrix\AdminKit\Page\ResourcePage;
use MB\Bitrix\AdminKit\Page\Standalone\CustomPage;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PageArchitectureTest extends TestCase
{
    public function testPageCanBeCreatedWithoutResource(): void
    {
        $page = new class () extends StandalonePage {
            public static function getId(): string
            {
                return 'standalone-core';
            }

            public static function getTitle(): string
            {
                return 'Standalone';
            }

            public function render(): void
            {
            }
        };

        self::assertSame('standalone_core', $page->id());
    }

    public function testResourcePageRequiresResourceForResourceAccessor(): void
    {
        $page = new class () extends ResourcePage {
            public function render(): void
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $page->resource();
    }

    public function testStandalonePageDoesNotRequireResource(): void
    {
        $page = new class () extends CustomPage {
            public static function getId(): string
            {
                return 'custom-test';
            }

            public static function getTitle(): string
            {
                return 'Custom test';
            }

            protected function content(): string
            {
                return 'ok';
            }
        };

        self::assertSame('custom_test', $page->id());
    }

    public function testCrudPagesExtendResourcePage(): void
    {
        self::assertTrue(is_subclass_of(CrudIndexPage::class, CrudPage::class));
        self::assertTrue(is_subclass_of(CrudFormPage::class, ResourcePage::class));
        self::assertTrue(is_subclass_of(CrudDetailPage::class, CrudPage::class));
    }

    public function testCrudPageRejectsCoreOnlyResourceWithClearException(): void
    {
        $resource = new class () extends Resource {
            protected string $title = 'Core only';
        };
        $page = new class ($resource) extends CrudPage {
            public function render(): void
            {
            }

            protected static function defaultPageType(): PageType
            {
                return PageType::INDEX;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires an instance of');

        $page->resource();
    }

    public function testStandalonePageExtendsCorePage(): void
    {
        self::assertTrue(is_subclass_of(StandalonePage::class, Page::class));
    }
}
