<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

final class FakeEntityCollection implements \IteratorAggregate
{
    /** @param list<object> $items */
    public function __construct(private array $items)
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}

final class FakeSimpleEntityObject
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values)
    {
    }

    public function get(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }

    public function getId(): mixed
    {
        return $this->values['ID'] ?? null;
    }
}

final class FakeSaveResult
{
    public function __construct(private bool $success, private mixed $id)
    {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getId(): mixed
    {
        return $this->id;
    }
}

final class FakePivotTableForSync
{
    /** @var list<array<string,mixed>> */
    public static array $rows = [];

    public static function reset(): void
    {
        self::$rows = [];
    }

    public static function getList(array $params): object
    {
        $ownerId = $params['filter']['OWNER_ID'] ?? null;
        $rows = array_values(array_filter(self::$rows, static fn (array $row): bool => (string) $row['OWNER_ID'] === (string) $ownerId));

        return new class ($rows) {
            private int $i = 0;

            /** @param list<array<string,mixed>> $rows */
            public function __construct(private array $rows)
            {
            }

            public function fetch(): array|false
            {
                return $this->rows[$this->i++] ?? false;
            }
        };
    }

    public static function delete(array $filter): void
    {
        self::$rows = array_values(array_filter(
            self::$rows,
            static fn (array $row): bool => !((string) $row['OWNER_ID'] === (string) $filter['OWNER_ID'] && (string) $row['TAG_ID'] === (string) $filter['TAG_ID']),
        ));
    }

    public static function add(array $data): void
    {
        self::$rows[] = $data;
    }
}
