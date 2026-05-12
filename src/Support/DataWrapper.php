<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use ArrayAccess;

class DataWrapper implements ArrayAccess
{
    public function __construct(
        protected array $data = [],
        protected int|string|null $id = null,
    ) {
    }

    public static function fromArray(array $data, string $primaryKey = 'ID'): static
    {
        $id = $data[$primaryKey] ?? null;

        return new static($data, $id);
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): static
    {
        unset($this->data[$key]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string)$offset);
    }
}
