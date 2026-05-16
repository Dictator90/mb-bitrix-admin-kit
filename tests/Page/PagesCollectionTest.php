<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use LogicException;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Page\Pages;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class PagesCollectionTest extends TestCase
{
    public function testMakeAcceptsClassStrings(): void
    {
        $pages = Pages::make([IndexPage::class, FormPage::class])
            ->setResource(new ProductResource());

        self::assertCount(2, $pages);
        self::assertInstanceOf(IndexPage::class, $pages->findByName('index'));
    }

    public function testSetResourceAppliesToResourcePages(): void
    {
        $resource = new ProductResource();
        $page = Pages::make([IndexPage::class])->setResource($resource)->indexPage();

        self::assertNotNull($page);
        self::assertSame($resource, $page->resource());
    }

    public function testFindByTypeAndClass(): void
    {
        $pages = Pages::make([IndexPage::class, FormPage::class])
            ->setResource(new ProductResource());

        self::assertInstanceOf(IndexPage::class, $pages->findByType(PageType::INDEX));
        self::assertInstanceOf(FormPage::class, $pages->findByClass(FormPage::class));
    }

    public function testDuplicatePageNameThrows(): void
    {
        $this->expectException(LogicException::class);

        Pages::make([IndexPage::class, IndexPage::class]);
    }
}
