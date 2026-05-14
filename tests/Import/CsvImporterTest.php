<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CsvImporterTest extends TestCase
{
    protected function setUp(): void { ProductTable::reset(); }

    public function testItCreatesRowsFromMappedCsvData(): void
    {
        $importer = new CsvImporter();
        $context = new ImportContext(new ProductResource(), mode: 'create');
        $mapped = $importer->mapRows([['Name' => 'Created']], ['Name' => 'NAME'], $context);

        $result = $importer->importRows($mapped);

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $result->created);
        self::assertSame(['NAME' => 'Created'], ProductTable::$lastAdded);
    }
}
