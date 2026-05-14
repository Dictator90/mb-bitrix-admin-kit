<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Schema;

use MB\Bitrix\AdminKit\Database\Schema\DatabaseSchemaInspector;
use PHPUnit\Framework\TestCase;

final class DatabaseSchemaInspectorTest extends TestCase
{
    public function testReadsTableColumnsAndIndexesFromConnection(): void
    {
        $connection = new class {
            public function isTableExists(string $table): bool { return $table === 'vendor_product'; }
            public function getTableFields(string $table): array { return ['ID' => ['type' => 'int'], 'NAME' => ['type' => 'varchar(255)']]; }
            public function getTableIndexes(string $table): array { return ['PRIMARY' => ['columns' => ['ID']]]; }
        };

        $inspector = new DatabaseSchemaInspector($connection);

        self::assertTrue($inspector->tableExists('vendor_product'));
        self::assertTrue($inspector->columnExists('vendor_product', 'NAME'));
        self::assertSame('int', $inspector->getColumns('vendor_product')['ID']['type']);
        self::assertSame(['ID'], $inspector->getIndexes('vendor_product')['PRIMARY']['columns']);
    }
}
