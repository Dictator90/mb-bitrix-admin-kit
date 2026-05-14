<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ImportParserTest extends TestCase
{
    public function testItParsesHeaderBasedCsvFiles(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'adminkit-import-');
        file_put_contents($file, "Name\nOne\nTwo\n");

        $importer = new CsvImporter();
        $result = $importer->parseUploadedFile($file, new ImportContext(new ProductResource()));

        @unlink($file);

        self::assertTrue($result->isSuccess());
        self::assertSame(2, $result->total);
        self::assertSame([['Name' => 'One'], ['Name' => 'Two']], $importer->parsedRows());
    }

    public function testItLimitsImportedRows(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'adminkit-import-');
        file_put_contents($file, "Name\nOne\nTwo\n");

        $result = (new CsvImporter())->parseUploadedFile($file, new ImportContext(new ProductResource(), maxRows: 1));
        @unlink($file);

        self::assertFalse($result->isSuccess());
        self::assertArrayHasKey('limit', $result->errors);
    }
}
