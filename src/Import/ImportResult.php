<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Import;

final class ImportResult
{
    /** @param array<int|string,mixed> $errors */
    public function __construct(
        public readonly int $total = 0,
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $skipped = 0,
        public readonly array $errors = [],
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function isSuccess(): bool
    {
        return $this->errors === [];
    }

    public function withCreated(int $count = 1): self
    {
        return new self($this->total, $this->created + $count, $this->updated, $this->skipped, $this->errors);
    }

    public function withUpdated(int $count = 1): self
    {
        return new self($this->total, $this->created, $this->updated + $count, $this->skipped, $this->errors);
    }

    public function withSkipped(int $count = 1): self
    {
        return new self($this->total, $this->created, $this->updated, $this->skipped + $count, $this->errors);
    }

    public function withTotal(int $total): self
    {
        return new self($total, $this->created, $this->updated, $this->skipped, $this->errors);
    }

    public function addError(int|string $row, string|array $message): self
    {
        $errors = $this->errors;
        $errors[$row] = array_values((array)$message);

        return new self($this->total, $this->created, $this->updated, $this->skipped, $errors);
    }
}
