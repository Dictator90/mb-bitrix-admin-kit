<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

/**
 * In-memory owner object for relation mutator/sync unit tests.
 */
final class MutatorFakeEntityObject
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values = [])
    {
    }

    public function set(string $fieldName, mixed $value): self
    {
        $this->values[$fieldName] = $value;

        return $this;
    }

    public function get(string $fieldName): mixed
    {
        return $this->values[$fieldName] ?? null;
    }

    public function getId(): mixed
    {
        return $this->values['ID'] ?? null;
    }
}
