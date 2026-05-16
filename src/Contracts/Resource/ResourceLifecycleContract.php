<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;

interface ResourceLifecycleContract
{
    public function beforeValidate(FormData $data, DbOperationContext $context): void;

    public function afterValidate(FormData $data, DbOperationContext $context): void;

    public function beforeCreate(FormData $data, DbOperationContext $context): void;

    public function afterCreate(mixed $id, FormData $data, DbOperationContext $context): void;

    public function beforeUpdate(mixed $id, FormData $data, DbOperationContext $context): void;

    public function afterUpdate(mixed $id, FormData $data, DbOperationContext $context): void;

    public function beforeDelete(mixed $id, DbOperationContext $context): void;

    public function afterDelete(mixed $id, DbOperationContext $context): void;

    /** @param array<int,mixed> $ids */
    public function beforeMassDelete(array $ids, DbOperationContext $context): void;

    /** @param array<int,mixed> $ids */
    public function afterMassDelete(array $ids, DbOperationContext $context): void;
}
