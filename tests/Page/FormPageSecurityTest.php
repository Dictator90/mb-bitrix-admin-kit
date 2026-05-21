<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FormPageSecurityTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setGetRequest();
        $GLOBALS['APPLICATION'] = new class () {
            public function SetTitle(string $title): void
            {
            }

            public function IncludeComponent(string $name, string $template, array $params): void
            {
            }
        };
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testAsyncSaveWithoutSessidReturnsJsonError(): void
    {
        $resource = new FormSaveTrackingResource();
        $this->setAjaxPostRequest([
            'NAME' => 'Test',
            'adminkit_async_save' => 'Y',
            'sessid' => 'invalid',
        ]);
        $page = new FormPage($resource);

        ob_start();
        try {
            $page->render();
        } catch (\Throwable) {
            // sendAsyncSaveResponse ends with terminate().
        }
        $json = (string)ob_get_clean();
        preg_match('/\{.*\}/s', $json, $matches);
        $payload = json_decode($matches[0] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($payload['success']);
        self::assertFalse($payload['validationError']);
        self::assertFalse($payload['closeSidePanel']);
        self::assertSame([], $payload['fieldErrors']);
        self::assertNotEmpty($payload['globalErrors'] ?? []);
        self::assertSame(0, FormSaveTrackingResource::$createCalls);
        self::assertSame(0, FormSaveTrackingResource::$updateCalls);
    }

    public function testPostWithoutSessidDoesNotCreateOrUpdate(): void
    {
        $resource = new FormSaveTrackingResource();
        $this->setPostRequest(['NAME' => 'Test', 'sessid' => 'invalid']);
        $page = new FormPage($resource);

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertSame(0, FormSaveTrackingResource::$createCalls);
        self::assertSame(0, FormSaveTrackingResource::$updateCalls);
        self::assertNotSame('', trim($html));
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

        self::assertTrue(str_contains($html, 'Item not found.') || str_contains($html, 'not found'));
        self::assertStringNotContainsString('data-field-column="NAME"', $html);

        FormSaveTrackingResource::$updateCalls = 0;
        $this->setPostRequest(['NAME' => 'Test']);
        $resource = new FormSaveTrackingResource();
        $page = new FormPage($resource, 404);

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
