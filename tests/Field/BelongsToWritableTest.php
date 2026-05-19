<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class BelongsToWritableTest extends TestCase
{
    public function testBelongsToIsWritableByDefault(): void
    {
        $field = BelongsTo::make('Owner', 'CREATED_BY', ProductTable::class);

        self::assertFalse($field->isReadOnly());
    }

    public function testBelongsToCanBeMarkedReadonly(): void
    {
        $field = BelongsTo::make('Iblock', 'IBLOCK_ID', ProductTable::class)->readonly();

        self::assertTrue($field->isReadOnly());
    }
}
