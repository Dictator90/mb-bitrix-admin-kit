<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface FilterContract
{
    public function getColumn(): string;

    public function getLabel(): string;

    public function getFilterFieldConfig(): array;

    public function prepareFieldData(): array;

    public function apply(array $filter, mixed $value): array;
}
