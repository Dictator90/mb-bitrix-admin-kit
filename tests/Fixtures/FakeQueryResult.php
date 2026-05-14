<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class FakeQueryResult
{
    private int $i = 0;
    public function __construct(private array $rows)
    {
    }
    public function fetch(): array|false
    {
        return $this->rows[$this->i++] ?? false;
    }
    public function fetchAll(): array
    {
        return $this->rows;
    }
}
