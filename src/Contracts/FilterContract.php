<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Grid\GridContext;

interface FilterContract
{
    public function getColumn(): string;

    public function getLabel(): string;

    public function getFilterFieldConfig(): array;

    public function prepareFieldData(): array;

    public function apply(mixed $filter, mixed $value = null): mixed;

    public function applyToOrmFilter(array $filter, mixed $value, GridContext $context): array;
}
