<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class FormPageSidePanelAsyncTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = ['IFRAME' => 'Y'];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [
            'NAME' => 'Saved item',
            'adminkit_async_save' => 'Y',
            'sessid' => 'sessid',
        ];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];
    }

    public function testSuccessfulAsyncSaveInsideSidePanelSetsCloseSidePanelFlag(): void
    {
        $page = new FormPage(new ProductResource());

        $handlePost = new ReflectionMethod(FormPage::class, 'handlePost');
        $handlePost->setAccessible(true);
        $handlePost->invoke($page);

        $savedFlag = new ReflectionProperty(FormPage::class, 'savedInSidePanel');
        $savedFlag->setAccessible(true);

        self::assertTrue($savedFlag->getValue($page));

        $hasValidationErrors = new ReflectionProperty(FormPage::class, 'hasValidationErrors');
        $hasValidationErrors->setAccessible(true);

        $globalErrors = new ReflectionProperty(FormPage::class, 'globalErrors');
        $globalErrors->setAccessible(true);

        $payload = [
            'success' => !$hasValidationErrors->getValue($page) && $globalErrors->getValue($page) === [],
            'closeSidePanel' => $savedFlag->getValue($page),
        ];

        self::assertTrue($payload['success']);
        self::assertTrue($payload['closeSidePanel']);
    }
}
