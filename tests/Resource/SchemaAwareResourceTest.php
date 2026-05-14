<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class SchemaAwareResourceTest extends TestCase
{
    public function testCrudResourceResolvesDatabaseTableNameFromDataManager(): void
    {
        self::assertSame('vendor_product', (new ProductResource())->databaseTableName());
    }

    public function testSchemaAwareResourceReturnsExpectedSchema(): void
    {
        $resource = new class extends ProductResource implements SchemaAwareResource {
            public function expectedTableSchema(): TableSchema { return TableSchema::make('vendor_product')->column('ID', 'int', required: true); }
        };

        self::assertSame('vendor_product', $resource->expectedTableSchema()->tableName());
    }
}
