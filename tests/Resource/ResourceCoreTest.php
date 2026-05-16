<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use PHPUnit\Framework\TestCase;

final class ResourceCoreTest extends TestCase
{
    public function testCoreResourceHasIdentityAndMenu(): void
    {
        $resource = new CoreTestResource();

        self::assertSame('core-test', CoreTestResource::getId());
        self::assertSame('Core Test', $resource->getTitle());
        self::assertSame(100, CoreTestResource::getSort());
        self::assertSame('', CoreTestResource::getMenuIcon());
        self::assertTrue(CoreTestResource::isVisibleInMenu());
        self::assertNull(CoreTestResource::getParentMenuId());
        self::assertNull($resource->group());
    }

    public function testCoreResourceHasPages(): void
    {
        $resource = new CoreTestResource();

        self::assertCount(3, iterator_to_array($resource->pages()));
        self::assertInstanceOf(IndexPage::class, $resource->indexPage());
        self::assertInstanceOf(FormPage::class, $resource->formPage());
        self::assertInstanceOf(DetailPage::class, $resource->detailPage(1));
    }
}

final class CoreTestResource extends Resource
{
    protected string $title = 'Core Test';
}
