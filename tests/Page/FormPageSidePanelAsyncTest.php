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

final class FormPageSidePanelAsyncTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        ProductTable::reset();
        $this->setPostRequest([
            'NAME' => 'Saved item',
            'adminkit_async_save' => 'Y',
        ], ['IFRAME' => 'Y']);
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
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

        $closeMethod = new ReflectionMethod(FormPage::class, 'closeSidePanelAfterSave');
        $closeMethod->setAccessible(true);

        $success = !$hasValidationErrors->getValue($page) && $globalErrors->getValue($page) === [];
        $payload = [
            'success' => $success,
            'closeSidePanel' => $success && $savedFlag->getValue($page) && $closeMethod->invoke($page),
            'reloadParentGrid' => $success && $savedFlag->getValue($page),
        ];

        self::assertTrue($payload['success']);
        self::assertTrue($payload['closeSidePanel']);
        self::assertTrue($payload['reloadParentGrid']);
    }
}
