<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Traits;

use MB\Bitrix\AdminKit\Support\DataWrapper;

trait HasCrud
{
    public function findItem(int|string $id): ?DataWrapper
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return null;
        }

        $row = $class::getByPrimary($id)->fetch();
        if (!$row) {
            return null;
        }

        return DataWrapper::fromArray($row, $this->getPrimaryKey());
    }

    public function getList(array $params = []): array
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return [];
        }

        return $class::getList($params)->fetchAll();
    }

    public function save(DataWrapper $item): DataWrapper
    {
        $class = $this->getDataManagerClass();
        $primaryKey = $this->getPrimaryKey();
        $data = $item->toArray();

        if ($item->getId()) {
            $id = $item->getId();

            $item = $this->beforeUpdating($item);
            $updateData = $item->toArray();
            unset($updateData[$primaryKey]);
            $result = $class::update($id, $updateData);

            if ($result->isSuccess()) {
                $item->setId($id);
                $item = $this->afterUpdated($item);
            }
        } else {
            $item = $this->beforeCreating($item);
            $result = $class::add($item->toArray());

            if ($result->isSuccess()) {
                $item->setId($result->getId());
                $item = $this->afterCreated($item);
            }
        }

        return $item;
    }

    public function delete(int|string $id): bool
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return false;
        }

        $item = $this->findItem($id);
        if (!$item) {
            return false;
        }

        $this->beforeDeleting($item);
        $result = $class::delete($id);

        if ($result->isSuccess()) {
            $this->afterDeleted($item);
            return true;
        }

        return false;
    }

    public function massDelete(array $ids): void
    {
        $class = $this->getDataManagerClass();
        if (!$class) {
            return;
        }

        $this->beforeMassDeleting($ids);

        foreach ($ids as $id) {
            $class::delete($id);
        }

        $this->afterMassDeleted($ids);
    }
}
