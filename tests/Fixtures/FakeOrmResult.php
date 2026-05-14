<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class FakeOrmResult
{
    public function __construct(private bool $success = true, private mixed $id = 1, private array $errors = [])
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
    public function getErrorMessages(): array
    {
        return $this->errors;
    }
}
