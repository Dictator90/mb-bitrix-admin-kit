<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Traits;

use MB\Bitrix\AdminKit\Database\CrudPersister;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Database\TransactionManager;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use RuntimeException;

trait HasCrud
{
    public function findItem(mixed $id): ?array
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return null;
        }

        $row = $class::getList([
            'filter' => [$this->getPrimaryKey() => $id],
            'limit' => 1,
        ])->fetch();

        return $row ?: null;
    }

    public function getList(array $params = []): array
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return [];
        }

        return $class::getList($params)->fetchAll();
    }

    public function getCount(array $filter = []): int
    {
        $class = $this->getDataManagerClass();
        return $class ? (int)$class::getCount($filter) : 0;
    }

    public function useTransactions(): bool
    {
        return true;
    }

    public function createItem(array $data): mixed
    {
        $result = $this->createItemResult(new FormData($data, $data, $data));
        return (new CrudPersister())->requireSuccess($result);
    }

    public function createItemResult(FormData|array $data, ?DbOperationContext $context = null): DbResult
    {
        $class = $this->requireDataManagerClass();
        $formData = $data instanceof FormData ? $data : new FormData($data, $data, $data);
        $context ??= $this->makeOperationContext('create', null, [], $formData);

        return (new TransactionManager())->run(function () use ($class, $formData, $context): DbResult {
            $this->beforeCreate($formData, $context);
            $this->sendBitrixEvent('OnBeforeAdminKitResourceCreate', $context, $formData->validated(), null);

            $result = (new CrudPersister())->create($class, $formData->validated());
            if (!$result->isSuccess()) {
                return $result;
            }

            $this->afterCreate($result->id(), $formData, $context);
            $this->sendBitrixEvent('OnAfterAdminKitResourceCreate', $context, $formData->validated(), $result->id());

            return $result;
        }, $this->useTransactions());
    }

    public function updateItem(mixed $id, array $data): bool
    {
        $result = $this->updateItemResult($id, new FormData($data, $data, $data));
        (new CrudPersister())->requireSuccess($result);

        return true;
    }

    public function updateItemResult(mixed $id, FormData|array $data, ?DbOperationContext $context = null): DbResult
    {
        $class = $this->requireDataManagerClass();
        $oldItem = $this->findItem($id) ?? [];
        $formData = $data instanceof FormData ? $data : new FormData($data, $data, $data);
        $context ??= $this->makeOperationContext('update', $id, $oldItem, $formData);

        return (new TransactionManager())->run(function () use ($class, $id, $oldItem, $formData, $context): DbResult {
            $this->beforeUpdate($oldItem, $formData, $context);
            $this->sendBitrixEvent('OnBeforeAdminKitResourceUpdate', $context, $formData->validated(), $id);

            $updateData = $formData->validated();
            unset($updateData[$this->getPrimaryKey()]);

            $result = (new CrudPersister())->update($class, $id, $updateData);
            if (!$result->isSuccess()) {
                return $result;
            }

            $item = array_merge($oldItem, $updateData, [$this->getPrimaryKey() => $id]);
            $this->afterUpdate($item, $formData, $context);
            $this->sendBitrixEvent('OnAfterAdminKitResourceUpdate', $context, $item, $id);

            return $result;
        }, $this->useTransactions());
    }

    public function deleteItem(mixed $id): bool
    {
        $result = $this->deleteItemResult($id);
        (new CrudPersister())->requireSuccess($result);

        return true;
    }

    public function deleteItemResult(mixed $id, ?DbOperationContext $context = null): DbResult
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return DbResult::error('DataManager class is not configured.');
        }

        $row = $this->findItem($id);
        if (!$row) {
            return DbResult::error('Item was not found.');
        }

        $context ??= $this->makeOperationContext('delete', $id, $row, new FormData());

        return (new TransactionManager())->run(function () use ($class, $id, $row, $context): DbResult {
            $this->beforeDelete($row, $context);
            $this->sendBitrixEvent('OnBeforeAdminKitResourceDelete', $context, $row, $id);

            $result = (new CrudPersister())->delete($class, $id);
            if (!$result->isSuccess()) {
                return $result;
            }

            $this->afterDelete($row, $context);
            $this->sendBitrixEvent('OnAfterAdminKitResourceDelete', $context, $row, $id);

            return $result;
        }, $this->useTransactions());
    }

    public function save(DataWrapper $item): DataWrapper
    {
        if ($item->getId()) {
            $this->updateItem($item->getId(), $item->toArray());
            return $item;
        }

        $item->setId($this->createItem($item->toArray()));
        return $item;
    }

    public function delete(int|string $id): bool
    {
        return $this->deleteItem($id);
    }

    public function massDelete(array $ids): void
    {
        $context = $this->makeOperationContext('massDelete');
        (new TransactionManager())->run(function () use ($ids, $context): void {
            $this->beforeMassDelete($ids, $context);
            foreach ($ids as $id) {
                $this->deleteItem($id);
            }
            $this->afterMassDelete($ids, $context);
        }, $this->useTransactions());
    }

    protected function assertOrmResult(object $result): void
    {
        (new CrudPersister())->requireSuccess((new CrudPersister())->fromBitrixResult($result));
    }

    protected function makeOperationContext(
        string $operation,
        mixed $itemId = null,
        array $oldData = [],
        ?FormData $formData = null,
        mixed $userId = null,
        mixed $request = null,
    ): DbOperationContext {
        $formData ??= new FormData();

        return new DbOperationContext(
            resource: $this,
            operation: $operation,
            itemId: $itemId,
            oldData: $oldData,
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            userId: $userId,
            request: $request,
        );
    }

    protected function makePermissionContext(string $operation, array|object|null $item = null, mixed $userId = null): PermissionContext
    {
        return new PermissionContext($userId, null, $this, $operation, $item);
    }

    private function requireDataManagerClass(): string
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            throw new RuntimeException('DataManager class is not configured.');
        }

        return $class;
    }

    private function sendBitrixEvent(string $eventName, DbOperationContext $context, mixed $data, mixed $itemId): void
    {
        if (!class_exists('Bitrix\\Main\\Event')) {
            return;
        }

        $event = new \Bitrix\Main\Event('mb.bitrix.adminkit', $eventName, [
            'resource' => static::class,
            'context' => $context,
            'data' => $data,
            'itemId' => $itemId,
        ]);
        $event->send();
    }
}
