<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Page\PageFactory;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PageFactoryTest extends TestCase
{
    public function testUnknownClassThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        (new PageFactory())->make('MB\\Bitrix\\AdminKit\\Page\\UnknownPage', new ProductResource());
    }

    public function testClassNotImplementingPageContractThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must implement');

        (new PageFactory())->make(NotAPageClass::class, new ProductResource());
    }

    public function testAbstractPageClassThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be abstract');

        (new PageFactory())->make(AbstractTestPage::class, new ProductResource());
    }

    public function testValidClassCreatesPage(): void
    {
        $page = (new PageFactory())->make(IndexPage::class, new ProductResource());

        self::assertInstanceOf(IndexPage::class, $page);
        self::assertInstanceOf(PageContract::class, $page);
    }
}

final class NotAPageClass
{
}

abstract class AbstractTestPage extends FormPage
{
}
