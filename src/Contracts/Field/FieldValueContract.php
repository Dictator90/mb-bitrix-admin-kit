<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldValueContract
{
    public function getValue(): mixed;

    public function setValue(mixed $value): static;

    public function fill(mixed $value): static;

    /** @param array<string,mixed> $row */
    public function resolveValue(mixed $item, array $row = []): mixed;
}
