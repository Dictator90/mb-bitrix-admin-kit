<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use MB\Bitrix\AdminKit\Tests\Support\ComponentApplicationStub;
use PHPUnit\Framework\TestCase;

final class FormPageTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        ProductTable::reset();
        $this->setGetRequest();
        $GLOBALS['APPLICATION'] = new ComponentApplicationStub();
    }

    protected function tearDown(): void
    {
        ProductTable::reset();
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testLoadItemForEdit(): void
    {
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'Existing Product']];
        $resource = new ProductResource();
        $page = new FormPage($resource, 1);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertStringContainsString('Existing Product', $html);
        self::assertStringContainsString('name="NAME"', $html);
    }

    public function testCreateItemViaFormPage(): void
    {
        $resource = new ProductResource();
        $this->setPostRequest([
            'NAME' => 'New Product From Form',
        ]);
        $page = new FormPage($resource);

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('New Product From Form', ProductTable::$lastAdded['NAME']);
    }

    public function testUpdateItemViaFormPage(): void
    {
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'Old Product']];
        $resource = new ProductResource();
        $this->setPostRequest([
            'NAME' => 'Updated Product Name',
        ]);
        $page = new FormPage($resource, 1);

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame(1, ProductTable::$lastUpdated['id']);
        self::assertSame('Updated Product Name', ProductTable::$lastUpdated['data']['NAME']);
    }

    public function testItemNotFound(): void
    {
        $resource = new ProductResource();
        $page = new FormPage($resource, 999);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertTrue(str_contains($html, 'Р­Р»РµРјРµРЅС‚ РЅРµ РЅР°Р№РґРµРЅ.') || str_contains($html, 'Item was not found.'));
        self::assertStringNotContainsString('name="NAME"', $html);
    }

    public function testPermissionDeniedCreate(): void
    {
        $resource = new class () extends ProductResource {
            public function canCreate(?PermissionContext $context = null): bool
            {
                return false;
            }
        };
        $page = new FormPage($resource);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertStringContainsString('ui-alert', $html);
    }

    public function testPermissionDeniedEdit(): void
    {
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'Old Product']];
        $resource = new class () extends ProductResource {
            public function canUpdate(\MB\Bitrix\AdminKit\Security\PermissionContext|\MB\Bitrix\AdminKit\Support\DataWrapper|null $context = null): bool
            {
                return false;
            }
        };
        $page = new FormPage($resource, 1);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertStringContainsString('ui-alert', $html);
    }

    public function testValidationErrors(): void
    {
        $resource = new ProductResource();
        $this->setPostRequest([
            'NAME' => '', // Required field empty
        ]);
        $page = new FormPage($resource);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertTrue(str_contains($html, 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё.') || str_contains($html, 'Validation error.'));
        self::assertTrue(str_contains($html, 'РѕР±СЏР·Р°С‚РµР»СЊРЅРѕ РґР»СЏ Р·Р°РїРѕР»РЅРµРЅРёСЏ.') || str_contains($html, 'is required.'));
    }

    public function testLifecycleHooks(): void
    {
        $resource = new class () extends ProductResource {
            public array $calls = [];

            public function beforeValidate($data, $context): void
            {
                $this->calls[] = 'beforeValidate';
            }

            public function afterValidate($data, $context): void
            {
                $this->calls[] = 'afterValidate';
            }
        };

        $this->setPostRequest([
            'NAME' => 'Hook Test',
        ]);

        $page = new class ($resource) extends FormPage {
            public array $calls = [];

            protected function beforeSave($data, $context): void
            {
                $this->calls[] = 'beforeSave';
                $this->resource->calls[] = 'beforeSave';
            }

            protected function afterSave($data, $context, $savedId): void
            {
                $this->calls[] = 'afterSave';
                $this->resource->calls[] = 'afterSave';
            }
        };

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame([
            'beforeValidate',
            'beforeSave',
            'afterValidate',
            'afterSave'
        ], $resource->calls);
    }

    public function testReadonlyFieldsAreExcludedFromSaving(): void
    {
        $resource = new class () extends ProductResource {
            public function formFields(): iterable
            {
                return [
                    Text::make('Name', 'NAME'),
                    Text::make('Readonly Code', 'CODE')->readonly(),
                ];
            }
        };

        $this->setPostRequest([
            'NAME' => 'Product Name',
            'CODE' => 'HACKED_CODE',
        ]);
        $page = new FormPage($resource);

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertArrayNotHasKey('CODE', ProductTable::$lastAdded);
        self::assertSame('Product Name', ProductTable::$lastAdded['NAME']);
    }

    public function testSubmittedValuesPreservedOnValidationError(): void
    {
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'Old Name']];

        $resource = new class () extends ProductResource {
            public function formFields(): iterable
            {
                return [
                    Text::make('Name', 'NAME')->required(),
                    Text::make('Extra', 'EXTRA')->required(),
                ];
            }
        };

        $this->setPostRequest([
            'NAME' => 'Edited Name But Invalid Form',
            'EXTRA' => '', // triggers validation error
        ]);
        $page = new FormPage($resource, 1);

        ob_start();
        $page->render();
        $html = ob_get_clean();

        self::assertStringContainsString('Edited Name But Invalid Form', $html);
    }
}
