<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\CrudPersister;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CrudPersisterDeleteTest extends TestCase
{
    public function testDeleteReturnsFallbackId(): void
    {
        ProductTable::$nextDeleteErrors = [];
        $result = (new CrudPersister())->delete(ProductTable::class, 7);

        self::assertTrue($result->isSuccess());
        self::assertSame(7, $result->id());
    }
}
