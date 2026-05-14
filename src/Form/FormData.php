<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Form;

final class FormData
{
    public function __construct(
        private array $raw = [],
        private array $normalized = [],
        private array $validated = [],
        private array $errors = [],
    ) {
    }

    public function raw(): array
    {
        return $this->raw;
    }
    public function normalized(): array
    {
        return $this->normalized;
    }
    public function validated(): array
    {
        return $this->validated;
    }
    public function all(): array
    {
        return $this->validated ?: $this->normalized ?: $this->raw;
    }
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }
    public function set(string $key, mixed $value): void
    {
        $this->validated[$key] = $this->normalized[$key] = $value;
    }
    public function errors(): array
    {
        return $this->errors;
    }
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function withRaw(array $raw): self
    {
        $clone = clone $this;
        $clone->raw = $raw;
        return $clone;
    }
    public function withNormalized(array $normalized): self
    {
        $clone = clone $this;
        $clone->normalized = $normalized;
        return $clone;
    }
    public function withValidated(array $validated): self
    {
        $clone = clone $this;
        $clone->validated = $validated;
        return $clone;
    }
    public function withErrors(array $errors): self
    {
        $clone = clone $this;
        $clone->errors = $errors;
        return $clone;
    }
}
