<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use Bitrix\Main\Application;
use Throwable;

final class TransactionManager
{
    public function __construct(private ?object $connection = null) {}

    public function start(): void
    {
        $this->connection()->startTransaction();
    }

    public function commit(): void
    {
        $this->connection()->commitTransaction();
    }

    public function rollback(): void
    {
        $this->connection()->rollbackTransaction();
    }

    public function run(callable $callback, bool $enabled = true): mixed
    {
        if (!$enabled) {
            return $callback();
        }

        $this->start();

        try {
            $result = $callback();
            if ($result instanceof DbResult && !$result->isSuccess()) {
                $this->rollback();
                return $result;
            }

            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    private function connection(): object
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        return $this->connection = Application::getConnection();
    }
}
