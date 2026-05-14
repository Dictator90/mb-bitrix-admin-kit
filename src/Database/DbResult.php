<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

final class DbResult
{
    private bool $success = false;

    /** @var string[] */
    private array $errors = [];

    private mixed $id = null;

    public static function success(mixed $id = null): self
    {
        $result = new self();
        $result->success = true;
        $result->id = $id;

        return $result;
    }

    /** @param string|string[] $errors */
    public static function error(string|array $errors): self
    {
        $result = new self();
        $result->success = false;
        $result->errors = array_values(array_filter((array)$errors, static fn($error) => $error !== ''));

        if ($result->errors === []) {
            $result->errors[] = 'Bitrix ORM operation failed.';
        }

        return $result;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function id(): mixed
    {
        return $this->id;
    }

    /** @return string[] */
    public function errors(): array
    {
        return $this->errors;
    }
}
