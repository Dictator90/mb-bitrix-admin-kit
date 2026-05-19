<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductOrmEntityObject
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values)
    {
    }

    public function getId(): mixed
    {
        return $this->values['ID'] ?? null;
    }

    public function get(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }

    public function set(string $field, mixed $value): void
    {
        $this->values[$field] = $value;
    }

    /** @return array<string,mixed> */
    public function collectValues(): array
    {
        return $this->values;
    }

    public function save(): FakeOrmResult
    {
        $id = $this->values['ID'] ?? null;
        if ($id === null || $id === '') {
            $result = ProductTable::add($this->values);
            if ($result->isSuccess()) {
                $this->values['ID'] = $result->getId();
            }

            return $result;
        }

        $result = ProductTable::update($id, $this->values);
        if ($result->isSuccess()) {
            foreach (ProductTable::$rows as $index => $row) {
                if ((string) ($row['ID'] ?? '') === (string) $id) {
                    ProductTable::$rows[$index] = $this->values;
                    break;
                }
            }
        }

        return $result;
    }
}
