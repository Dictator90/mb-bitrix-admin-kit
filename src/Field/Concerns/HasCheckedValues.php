<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

/**
 * Shared checked/unchecked value handling for boolean-style fields
 * (Checkbox, Switcher): stored values, robust checked detection and
 * normalization of incoming POST/ORM values.
 */
trait HasCheckedValues
{
    protected string $checkedValue = 'Y';
    protected string $uncheckedValue = 'N';

    public function values(string $checked, string $unchecked): static
    {
        $this->checkedValue = $checked;
        $this->uncheckedValue = $unchecked;

        return $this;
    }

    public static function isCheckedValue(mixed $value, string $checkedValue = 'Y'): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value > 0;
        }

        $normalized = strtoupper((string) $value);

        return $normalized === strtoupper($checkedValue)
            || $normalized === '1'
            || $normalized === 'TRUE';
    }

    public function isCheckedState(mixed $value): bool
    {
        return self::isCheckedValue($value, $this->checkedValue);
    }

    public function normalize(mixed $value): mixed
    {
        return $this->isCheckedState($value) ? $this->checkedValue : $this->uncheckedValue;
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
    }
}
