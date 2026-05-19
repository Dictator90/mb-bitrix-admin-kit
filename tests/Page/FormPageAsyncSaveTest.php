<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class FormPageAsyncSaveTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [
            'NAME' => 'Saved item',
            'adminkit_async_save' => 'Y',
            'sessid' => 'sessid',
        ];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
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
