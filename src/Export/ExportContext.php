<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class ExportContext
{
    /** @var array<int,mixed> */
    public array $selectedIds;

    /** @var array<string,mixed> */
    public array $filter;

    /** @var array<int,FieldContract> */
    public array $fields;

    public function __construct(
        public readonly DataManagerResourceContract $resource,
        iterable $selectedIds = [],
        array $filter = [],
        iterable $fields = [],
        public readonly mixed $userId = null,
        public readonly string $format = 'csv',
        public readonly ?GridContext $gridContext = null,
    ) {
        $this->selectedIds = array_values(AdminCollection::make($selectedIds)->all());
        $this->filter = AdminCollection::make($filter)->all();
        $this->fields = array_values(array_filter(
            AdminCollection::make($fields)->all(),
            static fn (mixed $field): bool => $field instanceof FieldContract,
        ));
    }

    public function hasSelectedIds(): bool
    {
        return $this->selectedIds !== [];
    }

    public function hasFilter(): bool
    {
        return $this->filter !== [];
    }
}
