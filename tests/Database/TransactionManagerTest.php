<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use Exception;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Database\TransactionManager;
use PHPUnit\Framework\TestCase;

final class TransactionManagerTest extends TestCase
{
    public function testCommitsSuccessfulCallback(): void
    {
        $connection = new class () {
            public array $calls = [];
            public function startTransaction(): void
            {
                $this->calls[] = 'start';
            } public function commitTransaction(): void
            {
                $this->calls[] = 'commit';
            } public function rollbackTransaction(): void
            {
                $this->calls[] = 'rollback';
            }
        };
        $value = (new TransactionManager($connection))->run(fn () => 'ok');

        self::assertSame('ok', $value);
        self::assertSame(['start', 'commit'], $connection->calls);
    }


    public function testRollbacksFailedDbResult(): void
    {
        $connection = new class () {
            public array $calls = [];
            public function startTransaction(): void
            {
                $this->calls[] = 'start';
            } public function commitTransaction(): void
            {
                $this->calls[] = 'commit';
            } public function rollbackTransaction(): void
            {
                $this->calls[] = 'rollback';
            }
        };
        $result = (new TransactionManager($connection))->run(fn () => DbResult::error('failed'));

        self::assertFalse($result->isSuccess());
        self::assertSame(['start', 'rollback'], $connection->calls);
    }

    public function testRollbacksFailedCallback(): void
    {
        $connection = new class () {
            public array $calls = [];
            public function startTransaction(): void
            {
                $this->calls[] = 'start';
            } public function commitTransaction(): void
            {
                $this->calls[] = 'commit';
            } public function rollbackTransaction(): void
            {
                $this->calls[] = 'rollback';
            }
        };

        $this->expectException(Exception::class);
        try {
            (new TransactionManager($connection))->run(fn () => throw new Exception('fail'));
        } finally {
            self::assertSame(['start', 'rollback'], $connection->calls);
        }
    }
}
