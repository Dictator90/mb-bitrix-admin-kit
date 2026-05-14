<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\DbResult;
use PHPUnit\Framework\TestCase;

final class DbResultTest extends TestCase
{
    public function testSuccessAndErrorFactories(): void
    {
        $success = DbResult::success(10);
        self::assertTrue($success->isSuccess());
        self::assertSame(10, $success->id());

        $error = DbResult::error(['First', 'Second']);
        self::assertFalse($error->isSuccess());
        self::assertSame(['First', 'Second'], $error->errors());
    }
}
