<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FormPageSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = [];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = '';
    }

    protected function tearDown(): void
    {
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
    }

    public function testAsyncSaveWithoutSessidReturnsJsonError(): void
    {
        $resource = new FormSaveTrackingResource();
        $page = new FormPage($resource);

        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [
            'NAME' => 'Test',
            'adminkit_async_save' => 'Y',
        ];
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        ob_start();
        try {
            $page->render();
        } catch (\Throwable) {
            // sendAsyncSaveResponse ends with die().
        }
        $json = (string)ob_get_clean();
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($payload['success']);
        self::assertFalse($payload['validationError']);
        self::assertFalse($payload['closeSidePanel']);
        self::assertSame([], $payload['fieldErrors']);
        self::assertStringContainsString('Сессия истекла', $payload['globalErrors'][0] ?? '');
        self::assertSame(0, FormSaveTrackingResource::$createCalls);
        self::assertSame(0, FormSaveTrackingResource::$updateCalls);
    }

    public function testPostWithoutSessidDoesNotCreateOrUpdate(): void
    {
        $resource = new FormSaveTrackingResource();
        $page = new FormPage($resource);

        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['NAME' => 'Test'];
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertSame(0, FormSaveTrackingResource::$createCalls);
        self::assertSame(0, FormSaveTrackingResource::$updateCalls);
        self::assertStringContainsString('Сессия истекла', $html);
    }

    public function testEditWithMissingIdShowsNotFoundAndSkipsUpdate(): void
    {
        $resource = new class () extends ProductResource {
            public function findItem(mixed $id): ?array
            {
                return null;
            }
        };
        $page = new FormPage($resource, 404);

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('Элемент не найден.', $html);
        self::assertStringNotContainsString('data-field-column="NAME"', $html);

        $resource = new FormSaveTrackingResource();
        $page = new FormPage($resource, 404);
        FormSaveTrackingResource::$updateCalls = 0;

        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['NAME' => 'Test', 'sessid' => 'sessid'];
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];

        $handlePost = new ReflectionMethod(FormPage::class, 'handlePost');
        $handlePost->setAccessible(true);
        $handlePost->invoke($page);

        self::assertSame(0, FormSaveTrackingResource::$updateCalls);
    }
}

final class FormSaveTrackingResource extends ProductResource
{
    public static int $createCalls = 0;

    public static int $updateCalls = 0;

    public function createItemResult(\MB\Bitrix\AdminKit\Form\FormData|array $data, ?\MB\Bitrix\AdminKit\Database\DbOperationContext $context = null): \MB\Bitrix\AdminKit\Database\DbResult
    {
        self::$createCalls++;

        return parent::createItemResult($data, $context);
    }

    public function updateItemResult(mixed $id, \MB\Bitrix\AdminKit\Form\FormData|array $data, ?\MB\Bitrix\AdminKit\Database\DbOperationContext $context = null): \MB\Bitrix\AdminKit\Database\DbResult
    {
        self::$updateCalls++;

        return parent::updateItemResult($id, $data, $context);
    }
}
