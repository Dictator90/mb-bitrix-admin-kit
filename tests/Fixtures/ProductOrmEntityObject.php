<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductOrmEntityObject
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values, private bool $isNew = true)
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
        if ($this->isNew) {
            $data = $this->values;
            if (array_key_exists('ID', $data) && $data['ID'] === null) {
                unset($data['ID']);
            }
            $result = ProductTable::add($data);
            if ($result->isSuccess()) {
                $this->values['ID'] = $result->getId();
                $this->isNew = false;
            }

            return $result;
        }

        $id = $this->values['ID'] ?? null;
        $data = $this->values;
        if (array_key_exists('ID', $data)) {
            unset($data['ID']);
        }
        $result = ProductTable::update($id, $data);
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
