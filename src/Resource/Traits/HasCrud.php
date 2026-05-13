<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Traits;

use RuntimeException;
use MB\Bitrix\AdminKit\Support\DataWrapper;

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

    public function createItem(array $data): mixed
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            throw new RuntimeException('DataManager class is not configured.');
        }

        $item = $this->beforeCreating(DataWrapper::fromArray($data, $this->getPrimaryKey()));
        $result = $class::add($item->toArray());
        $this->assertOrmResult($result);
        $item->setId($result->getId());
        $this->afterCreated($item);

        return $result->getId();
    }

    public function updateItem(mixed $id, array $data): bool
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            throw new RuntimeException('DataManager class is not configured.');
        }

        $item = DataWrapper::fromArray($data, $this->getPrimaryKey())->setId($id);
        $item = $this->beforeUpdating($item);
        $updateData = $item->toArray();
        unset($updateData[$this->getPrimaryKey()]);

        $result = $class::update($id, $updateData);
        $this->assertOrmResult($result);
        $this->afterUpdated($item);

        return true;
    }

    public function deleteItem(mixed $id): bool
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return false;
        }

        $row = $this->findItem($id);
        if (!$row) {
            return false;
        }

        $item = DataWrapper::fromArray($row, $this->getPrimaryKey());
        $this->beforeDeleting($item);
        $result = $class::delete($id);
        $this->assertOrmResult($result);
        $this->afterDeleted($item);

        return true;
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
        $this->beforeMassDeleting($ids);
        foreach ($ids as $id) {
            $this->deleteItem($id);
        }
        $this->afterMassDeleted($ids);
    }

    protected function assertOrmResult(object $result): void
    {
        if (!method_exists($result, 'isSuccess') || $result->isSuccess()) {
            return;
        }

        $messages = [];
        if (method_exists($result, 'getErrorMessages')) {
            $messages = $result->getErrorMessages();
        }

        throw new RuntimeException($messages ? implode('; ', $messages) : 'Bitrix ORM operation failed.');
    }
}
