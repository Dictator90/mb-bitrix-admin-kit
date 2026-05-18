<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Import;

use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class ImportContext
{
    /** @var array<int,array<string,mixed>> */
    public array $rawRows;

    /** @var array<int,array<string,mixed>> */
    public array $mappedRows;

    public function __construct(
        public readonly DataManagerResourceContract $resource,
        iterable $rawRows = [],
        iterable $mappedRows = [],
        public readonly mixed $userId = null,
        public readonly string $mode = 'create',
        public readonly mixed $request = null,
        public readonly string $keyField = 'ID',
        public readonly int $maxRows = 1000,
        public readonly bool $validateOnly = false,
    ) {
        $this->rawRows = array_values(AdminCollection::make($rawRows)->all());
        $this->mappedRows = array_values(AdminCollection::make($mappedRows)->all());
    }

    public function withRows(iterable $rawRows, iterable $mappedRows = []): self
    {
        return new self(
            $this->resource,
            $rawRows,
            $mappedRows,
            $this->userId,
            $this->mode,
            $this->request,
            $this->keyField,
            $this->maxRows,
            $this->validateOnly,
        );
    }
}
