<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\System;

use MB\Bitrix\AdminKit\Database\Schema\DatabaseSchemaInspector;
use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Page\System\DatabaseHealthPage;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class DatabaseHealthPageTest extends TestCase
{
    public function testDiagnosticsAreReadOnlyAndReportResourceHealth(): void
    {
        $resource = new class () extends ProductResource implements SchemaAwareResource {
            public function expectedTableSchema(): TableSchema
            {
                return TableSchema::make('vendor_product')->column('ID', 'int', required: true)->index('PRIMARY', ['ID']);
            }
        };
        $inspector = new class () extends DatabaseSchemaInspector {
            public function tableExists(string $tableName): bool
            {
                return true;
            }
            public function getColumns(string $tableName): array
            {
                return ['ID' => ['type' => 'int']];
            }
            public function getIndexes(string $tableName): array
            {
                return ['PRIMARY' => ['columns' => ['ID']]];
            }
        };

        $rows = (new DatabaseHealthPage([$resource], $inspector))->diagnostics();

        self::assertSame('vendor_product', $rows[0]['tableName']);
        self::assertSame('ok', $rows[0]['status']);
    }
}
