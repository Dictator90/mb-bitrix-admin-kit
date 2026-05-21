<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use Closure;
use MB\Support\Conditionable\ConditionTree;

interface FieldValidationContract
{
    public function isRequired(): bool;

    public function required(bool $required = true): static;

    public function requiredWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static;

    /** @return list<string>|static */
    public function validate(mixed $value): array|static;

    /**
     * @param array<string,mixed> $allValues
     * @return list<string>
     */
    public function runValidation(mixed $value, array $allValues = []): array;
}
