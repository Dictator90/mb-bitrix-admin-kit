<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;

final class BulkOperationContext
{
    /**
     * @param array<int,mixed> $selectedIds
     * @param array<string,mixed> $filter
     */
    public function __construct(
        public readonly ResourceContract $resource,
        public readonly mixed $action,
        public readonly array $selectedIds = [],
        public readonly mixed $userId = null,
        public readonly ?HttpRequest $request = null,
        public readonly array $filter = [],
        public readonly ?GridContext $gridContext = null,
    ) {}

    /** @param array<string,mixed> $changes */
    public function with(array $changes): self
    {
        return new self(
            $changes['resource'] ?? $this->resource,
            $changes['action'] ?? $this->action,
            $changes['selectedIds'] ?? $this->selectedIds,
            $changes['userId'] ?? $this->userId,
            $changes['request'] ?? $this->request,
            $changes['filter'] ?? $this->filter,
            $changes['gridContext'] ?? $this->gridContext,
        );
    }
}
