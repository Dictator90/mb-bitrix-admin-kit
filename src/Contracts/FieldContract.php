<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

interface FieldContract
{
    public function getColumn(): string;

    public function getLabel(): string;

    public function getValue(): mixed;

    public function setValue(mixed $value): static;

    public function isRequired(): bool;

    public function isVisibleOn(PageType $pageType): bool;

    public function getGridColumnType(): string;

    public function getGridColumnConfig(): array;

    public function getFilterType(): ?string;

    public function renderFormField(mixed $value = null): string;

    public function getDefault(): mixed;

    public function getFieldAssembler(): ?FieldAssembler;

    public function previewValue(mixed $value): mixed;

    /** Read-only fields are displayed but never written back to the model on save. */
    public function isReadOnly(): bool;
}
