<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FormPageValidationLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['NAME' => 'Test', 'sessid' => 'sessid'];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = ['NAME' => 'Test', 'sessid' => 'sessid'];
    }

    public function testBeforeValidateErrorAppearsInFieldErrors(): void
    {
        $resource = new LifecycleValidationResource();
        $page = new TestableFormPage($resource);

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertTrue($page->hasValidationErrorsFlag());
        self::assertSame(['Hook validation failed.'], $page->fieldErrorsFor('_global'));
        self::assertSame(0, LifecycleValidationResource::$createCalls);
    }

    public function testBeforeSaveErrorAppearsInFieldErrors(): void
    {
        $resource = new LifecycleValidationResource();
        $page = new BeforeSaveValidationFormPage($resource);

        $resource::reset();
        $resource::$skipBeforeValidateError = true;

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertTrue($page->hasValidationErrorsFlag());
        self::assertContains('Custom beforeSave error.', $page->fieldErrorsFor('NAME') ?? []);
        self::assertSame(0, LifecycleValidationResource::$createCalls);
    }
}

class TestableFormPage extends FormPage
{
    public function hasValidationErrorsFlag(): bool
    {
        return $this->hasValidationErrors;
    }

    /** @return string[]|null */
    public function fieldErrorsFor(string $column): ?array
    {
        return $this->fieldErrors[$column] ?? null;
    }
}

class BeforeSaveValidationFormPage extends TestableFormPage
{
    protected function beforeSave(FormData $data, DbOperationContext $context): void
    {
        $data->addError('NAME', 'Custom beforeSave error.');
    }
}

final class LifecycleValidationResource extends ProductResource
{
    public static int $createCalls = 0;

    public static bool $skipBeforeValidateError = false;

    public static function reset(): void
    {
        self::$createCalls = 0;
        self::$skipBeforeValidateError = false;
    }

    public function beforeValidate(FormData $data, DbOperationContext $context): void
    {
        if (!self::$skipBeforeValidateError) {
            $data->addError('_global', 'Hook validation failed.');
        }
    }

    public function createItemResult(FormData|array $data, ?DbOperationContext $context = null): \MB\Bitrix\AdminKit\Database\DbResult
    {
        self::$createCalls++;

        return parent::createItemResult($data, $context);
    }
}
