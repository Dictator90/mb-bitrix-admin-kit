<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BelongsToNormalizeTest extends TestCase
{
    public function testEmptyForeignKeyNormalizesToNull(): void
    {
        $field = BelongsTo::make('Owner', 'CREATED_BY', ProductTable::class);

        self::assertNull($field->normalize(''));
        self::assertNull($field->normalize(null));
    }

    public function testNumericForeignKeyNormalizesToInt(): void
    {
        $field = BelongsTo::make('Owner', 'CREATED_BY', ProductTable::class);

        self::assertSame(42, $field->normalize('42'));
    }
}
