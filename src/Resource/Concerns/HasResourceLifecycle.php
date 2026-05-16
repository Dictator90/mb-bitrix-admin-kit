<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Support\DataWrapper;

trait HasResourceLifecycle
{
    public function beforeValidate(FormData $data, DbOperationContext $context): void
    {
    }

    public function afterValidate(FormData $data, DbOperationContext $context): void
    {
    }

    public function beforeCreate(FormData $data, DbOperationContext $context): void
    {
        $this->beforeCreating(DataWrapper::fromArray($data->validated(), $this->getPrimaryKey()));
    }

    public function afterCreate(mixed $id, FormData $data, DbOperationContext $context): void
    {
        $this->afterCreated(DataWrapper::fromArray($data->validated(), $this->getPrimaryKey())->setId($id));
    }

    public function beforeUpdate(mixed $id, FormData $data, DbOperationContext $context): void
    {
        // Note: $id is passed as first argument in new contract, but old trait used $oldItem array.
        // We'll keep compatibility by fetching oldItem if needed or just using DataWrapper.
        $this->beforeUpdating(DataWrapper::fromArray($data->validated(), $this->getPrimaryKey())->setId($id));
    }

    public function afterUpdate(mixed $id, FormData $data, DbOperationContext $context): void
    {
        // Note: old trait used $item array.
        $this->afterUpdated(DataWrapper::fromArray($data->validated(), $this->getPrimaryKey())->setId($id));
    }

    public function beforeDelete(mixed $id, DbOperationContext $context): void
    {
        $this->beforeDeleting(DataWrapper::fromArray($context->oldData, $this->getPrimaryKey()));
    }

    public function afterDelete(mixed $id, DbOperationContext $context): void
    {
        $this->afterDeleted(DataWrapper::fromArray($context->oldData, $this->getPrimaryKey()));
    }

    public function beforeMassDelete(array $ids, DbOperationContext $context): void
    {
        $this->beforeMassDeleting($ids);
    }

    public function afterMassDelete(array $ids, DbOperationContext $context): void
    {
        $this->afterMassDeleted($ids);
    }

    protected function beforeCreating(DataWrapper $item): DataWrapper
    {
        return $item;
    }

    protected function afterCreated(DataWrapper $item): DataWrapper
    {
        return $item;
    }

    protected function beforeUpdating(DataWrapper $item): DataWrapper
    {
        return $item;
    }

    protected function afterUpdated(DataWrapper $item): DataWrapper
    {
        return $item;
    }

    protected function beforeDeleting(DataWrapper $item): void
    {
    }

    protected function afterDeleted(DataWrapper $item): void
    {
    }

    /** @param array<int, mixed> $ids */
    protected function beforeMassDeleting(array $ids): void
    {
    }

    /** @param array<int, mixed> $ids */
    protected function afterMassDeleted(array $ids): void
    {
    }
}
