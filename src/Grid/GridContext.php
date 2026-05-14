<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

final class GridContext
{
    public function __construct(
        public readonly ResourceContract $resource,
        public readonly string $gridId,
        public readonly string $filterId,
        public readonly array $sort = [],
        public readonly array $filter = [],
        public readonly int $pageSize = 20,
        public readonly int $currentPage = 1,
        public readonly int $offset = 0,
        public readonly int $limit = 20,
        public readonly mixed $request = null,
    ) {
    }

    public static function make(ResourceContract $resource, mixed $request = null, array $overrides = []): self
    {
        return new self(
            $resource,
            $overrides['gridId'] ?? $resource->getGridId(),
            $overrides['filterId'] ?? $resource->getFilterId(),
            $overrides['sort'] ?? [],
            $overrides['filter'] ?? [],
            (int)($overrides['pageSize'] ?? 20),
            (int)($overrides['currentPage'] ?? 1),
            (int)($overrides['offset'] ?? 0),
            (int)($overrides['limit'] ?? ($overrides['pageSize'] ?? 20)),
            $request,
        );
    }
}
