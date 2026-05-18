<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Page\Pages;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ResourcePagesTest extends TestCase
{
    public function testCoreResourcePagesDefaultIsCrudSet(): void
    {
        $resource = new class () extends Resource {
            protected string $title = 'Core only';
        };

        self::assertSame([IndexPage::class, FormPage::class, DetailPage::class], iterator_to_array($resource->pages()));
    }

    public function testCrudResourceHasDefaultCrudPages(): void
    {
        $pages = iterator_to_array((new ProductResource())->pages());

        self::assertSame([IndexPage::class, FormPage::class, DetailPage::class], $pages);
    }

    public function testGetPagesCollectionResolvesCrudPages(): void
    {
        $resource = new ProductResource();
        $pages = $resource->getPages();

        self::assertInstanceOf(Pages::class, $pages);
        self::assertInstanceOf(IndexPage::class, $pages->indexPage());
        self::assertInstanceOf(FormPage::class, $pages->formPage());
        self::assertInstanceOf(DetailPage::class, $pages->detailPage());
    }

    public function testIndexPageUsesPagesCollection(): void
    {
        $resource = new ProductResource();

        self::assertInstanceOf(IndexPage::class, $resource->indexPage());
        self::assertSame($resource, $resource->indexPage()->resource());
    }
}
