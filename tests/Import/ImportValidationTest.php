<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ImportValidationTest extends TestCase
{
    public function testItValidatesMappedRowsWithoutWriting(): void
    {
        $context = new ImportContext(new ProductResource(), mappedRows: [['NAME' => '']]);
        $result = (new CsvImporter())->validateRows($context);

        self::assertFalse($result->isSuccess());
        self::assertSame(1, $result->total);
        self::assertSame(1, $result->skipped);
        self::assertArrayHasKey(1, $result->errors);
    }
}
