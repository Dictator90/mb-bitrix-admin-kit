<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminString;

trait HasResourceGrid
{
    public function getGridId(): string
    {
        $dm = method_exists($this, 'getDataManagerClass') ? $this->getDataManagerClass() : null;

        return AdminString::gridId($dm ?: static::class);
    }

    public function getFilterId(): string
    {
        $dm = method_exists($this, 'getDataManagerClass') ? $this->getDataManagerClass() : null;

        return AdminString::filterId($dm ?: static::class);
    }

    public function useTotalCount(GridContext $context): bool
    {
        return true;
    }

    public function countCacheTtl(GridContext $context): int
    {
        return 0;
    }

    public function maxPageSize(): int
    {
        return 200;
    }

    public function bulkChunkSize(): int
    {
        return 100;
    }
}
