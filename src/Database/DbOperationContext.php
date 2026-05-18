<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;

final class DbOperationContext
{
    public function __construct(
        public readonly DataManagerResourceContract $resource,
        public readonly string $operation,
        public readonly mixed $itemId = null,
        public readonly array $oldData = [],
        public readonly array $newData = [],
        public readonly array $rawData = [],
        public readonly array $normalizedData = [],
        public readonly array $validatedData = [],
        public readonly mixed $userId = null,
        public readonly ?HttpRequest $request = null,
    ) {
    }

    /** @param array<string,mixed> $changes */
    public function with(array $changes): self
    {
        return new self(
            $changes['resource'] ?? $this->resource,
            $changes['operation'] ?? $this->operation,
            $changes['itemId'] ?? $this->itemId,
            $changes['oldData'] ?? $this->oldData,
            $changes['newData'] ?? $this->newData,
            $changes['rawData'] ?? $this->rawData,
            $changes['normalizedData'] ?? $this->normalizedData,
            $changes['validatedData'] ?? $this->validatedData,
            $changes['userId'] ?? $this->userId,
            $changes['request'] ?? $this->request,
        );
    }
}
