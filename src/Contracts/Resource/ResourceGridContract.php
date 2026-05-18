<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Grid\GridContext;

interface ResourceGridContract
{
    public function getGridId(): string;

    public function getFilterId(): string;

    public function useTotalCount(GridContext $context): bool;

    public function countCacheTtl(GridContext $context): int;

    public function maxPageSize(): int;

    public function bulkChunkSize(): int;
}
