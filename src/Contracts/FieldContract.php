<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use Closure;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Support\Conditionable\ConditionTree;

interface FieldContract
{
    public function getColumn(): string;

    public function getLabel(): string;

    public function getValue(): mixed;

    public function setValue(mixed $value): static;

    public function fill(mixed $value): static;

    /** @param array<string,mixed> $row */
    public function resolveValue(mixed $item, array $row = []): mixed;

    public function isRequired(): bool;

    public function isVisibleOn(PageType $pageType): bool;

    public function getGridColumnType(): string;

    public function getGridColumnConfig(): array;

    public function getFilterType(): ?string;

    public function renderFormField(mixed $value = null): string;

    public function renderIndex(mixed $context, array $row = []): string;

    /** @param array<string,mixed> $data */
    public function renderForm(mixed $context = null, array $data = []): string;

    public function renderDetail(mixed $context, array $row = []): string;

    public function normalize(mixed $value): mixed;

    /** @return array<int,string>|static */
    public function validate(mixed $value): array|static;

    public function getDefault(): mixed;

    public function getFieldAssembler(): ?FieldAssembler;

    public function previewValue(mixed $value): mixed;

    /** Read-only fields are displayed but never written back to the model on save. */
    public function isReadOnly(): bool;

    public function default(mixed $value): static;

    public function required(bool $required = true): static;

    public function readonly(bool $readonly = true): static;

    public function multiple(bool $multiple = true): static;

    public function help(?string $text): static;

    public function placeholder(?string $text): static;

    public function visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static;

    public function requiredWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static;

    public function readonlyWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static;

    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static;

    public function displayUsing(Closure $callback): static;

    public function asEditLink(bool $enabled = true): static;

    public function linkToEdit(bool $enabled = true): static;

    public function shouldRenderAsEditLink(): bool;
}
