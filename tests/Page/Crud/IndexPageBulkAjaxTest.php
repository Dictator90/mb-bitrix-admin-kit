<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\Crud;

use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use PHPUnit\Framework\TestCase;

final class IndexPageBulkAjaxTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_POST['adminkit_bulk_ajax'], $_POST['adminkit_bulk_action']);
    }

    public function testItRecognizesBulkAjaxRequest(): void
    {
        $page = new class () extends IndexPage {};

        self::assertFalse($page->isBulkAjaxRequest());

        $_POST['adminkit_bulk_ajax'] = 'Y';
        self::assertTrue($page->isBulkAjaxRequest());

        unset($_POST['adminkit_bulk_ajax']);
        $_POST['adminkit_bulk_action'] = 'delete';
        self::assertTrue($page->isBulkAjaxRequest());
    }

    public function testLegacyMethodDelegatesToNewOne(): void
    {
        $page = new class () extends IndexPage {};

        $_POST['adminkit_bulk_ajax'] = 'Y';
        self::assertTrue($page->isLegacyBulkAjaxRequest());
    }
}
