<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Traits;

use MB\Bitrix\AdminKit\Support\DataWrapper;

trait HasLifecycleEvents
{
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

    protected function beforeDeleting(DataWrapper $item): void {}

    protected function afterDeleted(DataWrapper $item): void {}

    protected function beforeMassDeleting(array $ids): void {}

    protected function afterMassDeleted(array $ids): void {}
}
