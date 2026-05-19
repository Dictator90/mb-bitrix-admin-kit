<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductOrmQuery
{
    /** @var list<string> */
    private array $select = ['*'];

    private ?string $whereField = null;

    private mixed $whereValue = null;

    /** @param list<string> $select */
    public function setSelect(array $select): self
    {
        $this->select = $select;

        return $this;
    }

    public function where(string $field, mixed $value): self
    {
        $this->whereField = $field;
        $this->whereValue = $value;

        return $this;
    }

    public function fetchObject(): ?ProductOrmEntityObject
    {
        if ($this->whereField === null) {
            return null;
        }

        foreach (ProductTable::$rows as $row) {
            if ((string) ($row[$this->whereField] ?? '') === (string) $this->whereValue) {
                return new ProductOrmEntityObject($row);
            }
        }

        return null;
    }
}
