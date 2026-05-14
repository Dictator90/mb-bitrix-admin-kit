<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\CrudPersister;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CrudPersisterCreateTest extends TestCase
{
    public function testCreateReturnsDbResult(): void
    {
        ProductTable::$nextAddErrors = [];
        $result = (new CrudPersister())->create(ProductTable::class, ['NAME' => 'Two']);

        self::assertTrue($result->isSuccess());
        self::assertSame(2, $result->id());
    }

    public function testCreateConvertsOrmErrors(): void
    {
        ProductTable::$nextAddErrors = ['Name is required'];
        $result = (new CrudPersister())->create(ProductTable::class, ['NAME' => '']);

        self::assertFalse($result->isSuccess());
        self::assertSame(['Name is required'], $result->errors());
        ProductTable::$nextAddErrors = [];
    }
}
