<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class FormPageAsyncSaveTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        ProductTable::reset();
        $this->setAjaxPostRequest([
            'NAME' => 'Saved item',
            'adminkit_async_save' => 'Y',
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testAsyncSaveOutsideSidePanelDoesNotRedirectAndShowsSavedNotice(): void
    {
        $page = new FormPage(new ProductResource());

        $handlePost = new ReflectionMethod(FormPage::class, 'handlePost');
        $handlePost->setAccessible(true);
        $handlePost->invoke($page);

        $showSavedNotice = new ReflectionProperty(FormPage::class, 'showSavedNotice');
        $showSavedNotice->setAccessible(true);

        $savedInSidePanel = new ReflectionProperty(FormPage::class, 'savedInSidePanel');
        $savedInSidePanel->setAccessible(true);

        self::assertTrue($showSavedNotice->getValue($page));
        self::assertFalse($savedInSidePanel->getValue($page));
    }
}
