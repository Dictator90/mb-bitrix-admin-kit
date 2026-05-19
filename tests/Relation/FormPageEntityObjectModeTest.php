<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FormPageEntityObjectModeTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['NAME' => 'Updated', 'sessid' => 'sessid'];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = $GLOBALS['MB_ADMIN_KIT_TEST_POST'];
    }

    public function testArrayModeStillUsesLegacyHandlePost(): void
    {
        $resource = new ProductResource();
        $page = new EntityObjectFormTestPage($resource, 1);
        $page->setLoadedItem(DataWrapper::fromArray(['ID' => 1, 'NAME' => 'One']));

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertContains(1, ProductTable::$updatedIds);
    }

    public function testEntityObjectModeUsesDedicatedBranch(): void
    {
        $resource = new EntityObjectEnabledResource();
        $page = new EntityObjectFormTestPage($resource, 1);
        $page->setLoadedItem(DataWrapper::fromArray(['ID' => 1, 'NAME' => 'One']));
        $page->setEntityItem(new FakeFormEntityObject(['ID' => 1, 'NAME' => 'One']));

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertTrue($page->entityObjectBranchUsed());
    }
}

final class EntityObjectEnabledResource extends ProductResource
{
    public function __construct()
    {
        $this->enableEntityObjectForm(true);
    }

    public function findObject(mixed $id, array $relations = []): mixed
    {
        return new FakeFormEntityObject(['ID' => $id, 'NAME' => 'One']);
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}

final class EntityObjectFormTestPage extends FormPage
{
    private bool $entityObjectBranch = false;

    public function setLoadedItem(DataWrapper $item): void
    {
        $this->item = $item;
    }

    public function setEntityItem(object $entity): void
    {
        $this->entityItem = $entity;
    }

    public function entityObjectBranchUsed(): bool
    {
        return $this->entityObjectBranch;
    }

    protected function handleEntityObjectPost(): void
    {
        $this->entityObjectBranch = true;
    }
}

final class FakeFormEntityObject
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values)
    {
    }

    /** @return array<string,mixed> */
    public function collectValues(): array
    {
        return $this->values;
    }
}
