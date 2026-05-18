<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Performance;

use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;

final class QueryPerformanceContext
{
    /** @param array<string,mixed> $params */
    public function __construct(
        public readonly DataManagerResourceContract $resource,
        public readonly GridContext $gridContext,
        public readonly array $params = [],
        public readonly float $executionTime = 0.0,
        public readonly int $rowCount = 0,
        public readonly bool $countQueryUsed = false,
        public readonly bool $cacheUsed = false,
    ) {
    }

    /** @param array<string,mixed> $changes */
    public function with(array $changes): self
    {
        return new self(
            $changes['resource'] ?? $this->resource,
            $changes['gridContext'] ?? $this->gridContext,
            $changes['params'] ?? $this->params,
            $changes['executionTime'] ?? $this->executionTime,
            $changes['rowCount'] ?? $this->rowCount,
            $changes['countQueryUsed'] ?? $this->countQueryUsed,
            $changes['cacheUsed'] ?? $this->cacheUsed,
        );
    }
}
