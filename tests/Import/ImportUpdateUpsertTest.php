<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ImportUpdateUpsertTest extends TestCase
{
    protected function setUp(): void { ProductTable::reset(); }

    public function testItUpdatesByConfigurableKeyField(): void
    {
        $resource = new class extends ProductResource {
            public function formFields(): iterable { return [Text::make('ID', 'ID'), Text::make('Name', 'NAME')->required()]; }
        };

        $result = (new CsvImporter())->importRows(new ImportContext($resource, mappedRows: [['ID' => 1, 'NAME' => 'Updated']], mode: 'update', keyField: 'ID'));

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $result->updated);
        self::assertSame(['id' => 1, 'data' => ['NAME' => 'Updated']], ProductTable::$lastUpdated);
    }

    public function testItCreatesMissingRowsInUpsertMode(): void
    {
        $resource = new class extends ProductResource {
            public function formFields(): iterable { return [Text::make('ID', 'ID'), Text::make('Name', 'NAME')->required()]; }
        };

        $result = (new CsvImporter())->importRows(new ImportContext($resource, mappedRows: [['ID' => 99, 'NAME' => 'New']], mode: 'upsert', keyField: 'ID'));

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $result->created);
        self::assertSame(['ID' => 99, 'NAME' => 'New'], ProductTable::$lastAdded);
    }
}
