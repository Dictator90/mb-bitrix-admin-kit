<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ImportUsesFieldPipelineTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testImportUsesFieldNormalizeAndValidatePipeline(): void
    {
        $resource = new class () extends ProductResource {
            public function formFields(): iterable
            {
                return [new class ('Name', 'NAME') extends Text {
                    public function normalize(mixed $value): mixed
                    {
                        return strtoupper((string)$value);
                    }
                }];
            }
        };

        $result = (new CsvImporter())->importRows(new ImportContext($resource, mappedRows: [['NAME' => 'created']], mode: 'create'));

        self::assertTrue($result->isSuccess());
        self::assertSame(['NAME' => 'CREATED'], ProductTable::$lastAdded);
    }
}
