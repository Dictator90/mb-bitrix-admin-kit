<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Resource\CrudResource;
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

    public function testCrudResourceUsesLegacyHandlePost(): void
    {
        $resource = new ManualPersistenceCrudResource();
        $page = new EntityObjectFormTestPage($resource, 1);
        $page->setLoadedItem(DataWrapper::fromArray(['ID' => 1, 'NAME' => 'One']));

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertTrue(ManualPersistenceCrudResource::$updateCalled);
        self::assertFalse($page->dataManagerObjectBranchUsed());
    }

    public function testDataManagerResourceUsesEntityObjectBranch(): void
    {
        $resource = new ProductResource();
        $page = new EntityObjectFormTestPage($resource, 1);
        $page->setLoadedItem(DataWrapper::fromArray(['ID' => 1, 'NAME' => 'One']));
        $page->setEntityItem(new FakeFormEntityObject(['ID' => 1, 'NAME' => 'One']));

        $method = new ReflectionMethod(FormPage::class, 'handlePost');
        $method->setAccessible(true);
        $method->invoke($page);

        self::assertTrue($page->dataManagerObjectBranchUsed());
        self::assertNotContains(1, ProductTable::$updatedIds);
    }
}

final class ManualPersistenceCrudResource extends CrudResource implements ResourcePersistenceContract
{
    public static bool $updateCalled = false;

    public function findItem(mixed $id): ?array
    {
        return ['ID' => $id, 'NAME' => 'One'];
    }

    public function getList(array $params = []): array
    {
        return [];
    }

    public function getCount(array $filter = []): int
    {
        return 0;
    }

    public function getPrimaryKey(): string
    {
        return 'ID';
    }

    public function createItem(array $data): mixed
    {
        return 1;
    }

    public function createItemResult(FormData|array $data, ?DbOperationContext $context = null): DbResult
    {
        return DbResult::success(1);
    }

    public function updateItem(mixed $id, array $data): bool
    {
        return true;
    }

    public function updateItemResult(mixed $id, FormData|array $data, ?DbOperationContext $context = null): DbResult
    {
        self::$updateCalled = true;

        return DbResult::success($id);
    }

    public function deleteItem(mixed $id): bool
    {
        return true;
    }

    public function deleteItemResult(mixed $id, ?DbOperationContext $context = null): DbResult
    {
        return DbResult::success($id);
    }

    public function massDelete(array $ids): void
    {
    }

    public function save(DataWrapper $item): DataWrapper
    {
        return $item;
    }

    public function delete(int|string $id): bool
    {
        return true;
    }

    public function useTransactions(): bool
    {
        return false;
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}

final class EntityObjectFormTestPage extends FormPage
{
    private bool $dataManagerObjectBranch = false;

    public function setLoadedItem(DataWrapper $item): void
    {
        $this->item = $item;
    }

    public function setEntityItem(object $entity): void
    {
        $this->entityItem = $entity;
    }

    public function dataManagerObjectBranchUsed(): bool
    {
        return $this->dataManagerObjectBranch;
    }

    protected function handleDataManagerObjectPost(): void
    {
        $this->dataManagerObjectBranch = true;
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
