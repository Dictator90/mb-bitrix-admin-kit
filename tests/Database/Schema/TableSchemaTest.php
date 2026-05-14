<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Schema;

use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use PHPUnit\Framework\TestCase;

final class TableSchemaTest extends TestCase
{
    public function testFluentSchemaDefinition(): void
    {
        $schema = TableSchema::make('vendor_product')
            ->column('ID', 'int', required: true)
            ->column('NAME', 'string', required: true)
            ->index('PRIMARY', ['ID']);

        self::assertSame('vendor_product', $schema->tableName());
        self::assertSame('int', $schema->columns()['ID']['type']);
        self::assertSame(['ID'], $schema->indexes()['PRIMARY']['columns']);
        self::assertArrayHasKey('NAME', $schema->requiredColumns());
    }
}
