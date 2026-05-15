<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use PHPUnit\Framework\TestCase;

final class GridRowIdTest extends TestCase
{
    public function testPrefixesAndNormalizesIds(): void
    {
        self::assertSame('group:10', GridRowId::group(10));
        self::assertSame('item:55', GridRowId::item(55));
        self::assertTrue(GridRowId::isGroupId('group:10'));
        self::assertTrue(GridRowId::isItemId('item:55'));
        self::assertSame('55', GridRowId::normalizeItemId('item:55'));
        self::assertSame(55, GridRowId::normalizeItemId(55));
        self::assertSame('sku-42', GridRowId::normalizeItemId('sku-42'));
        self::assertNull(GridRowId::normalizeItemId('group:10'));
        self::assertSame('10', GridRowId::rawId('group:10'));
    }
}
