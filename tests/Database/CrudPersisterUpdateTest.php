<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database;

use MB\Bitrix\AdminKit\Database\CrudPersister;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CrudPersisterUpdateTest extends TestCase
{
    public function testUpdateReturnsFallbackId(): void
    {
        ProductTable::$nextUpdateErrors = [];
        $result = (new CrudPersister())->update(ProductTable::class, 7, ['NAME' => 'Updated']);

        self::assertTrue($result->isSuccess());
        self::assertSame(7, $result->id());
    }
}
