<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Schema;

use MB\Bitrix\AdminKit\Database\Schema\DatabaseSchemaInspector;
use MB\Bitrix\AdminKit\Database\Schema\TableHealthCheck;
use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use PHPUnit\Framework\TestCase;

final class TableHealthCheckTest extends TestCase
{
    public function testReportsMissingSchemaParts(): void
    {
        $inspector = new class extends DatabaseSchemaInspector {
            public function tableExists(string $tableName): bool { return true; }
            public function getColumns(string $tableName): array { return ['ID' => ['type' => 'int']]; }
            public function getIndexes(string $tableName): array { return []; }
        };
        $schema = TableSchema::make('vendor_product')
            ->column('ID', 'int', required: true)
            ->column('NAME', 'string', required: true)
            ->index('PRIMARY', ['ID']);

        $result = (new TableHealthCheck($inspector))->check($schema);

        self::assertSame(['NAME'], $result['missingColumns']);
        self::assertSame(['PRIMARY'], $result['missingIndexes']);
        self::assertSame('warning', $result['status']);
    }
}
