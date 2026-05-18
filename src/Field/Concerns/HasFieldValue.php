<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait HasFieldValue
{
    protected mixed $value = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function fill(mixed $value): static
    {
        return $this->setValue($value);
    }

    /** @param array<string,mixed> $row */
    public function resolveValue(mixed $item, array $row = []): mixed
    {
        if (is_array($item)) {
            return $item[$this->column] ?? $row[$this->column] ?? $this->value ?? $this->default;
        }

        if (is_object($item) && method_exists($item, 'get')) {
            return $item->get($this->column) ?? $row[$this->column] ?? $this->value ?? $this->default;
        }

        return $item ?? $row[$this->column] ?? $this->value ?? $this->default;
    }
}
