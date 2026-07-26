<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldContract extends
    FieldIdentityContract,
    FieldValueContract,
    FieldDefaultContract,
    FieldVisibilityContract,
    FieldRenderContract,
    FormFieldContract,
    FieldValidationContract,
    FieldGridColumnContract,
    FieldFilterContract,
    FieldExportContract,
    FieldImportContract,
    FieldSerializationContract,
    ReactiveFieldContract
{
    public function readonly(bool $readonly = true): static;

    public function readonlyWhen(string|\MB\Support\Conditionable\ConditionTree|\Closure $condition, ?string $operator = null, mixed $value = null): static;

    public function readonlyOnUpdate(bool $readonly = true): static;

    public function readonlyOnCreate(bool $readonly = true): static;

    public function isReadOnly(): bool;

    /** @param array<string,mixed> $data */
    public function isReadOnlyFor(array $data = []): bool;

    public function multiple(bool $multiple = true): static;

    public function hint(?string $hint): static;

    public function help(?string $text): static;

    public function placeholder(?string $text): static;

    public function displayUsing(\Closure $callback): static;
}
