<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Export;

use MB\Bitrix\AdminKit\Export\CsvExporter;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Field\Hidden;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class CsvExporterTest extends TestCase
{
    public function testItExportsIndexFieldsDisplayAndComputedValues(): void
    {
        $resource = new class () extends ProductResource {
            public function indexFields(): iterable
            {
                return [
                    Text::make('Name', 'NAME')->displayUsing(static fn (mixed $value): string => strtoupper((string)$value)),
                    Text::make('Computed', 'COMPUTED')->computed(static fn (array $row): string => $row['NAME'] . '-computed'),
                    Hidden::make('Secret', 'SECRET'),
                    Text::make('Private', 'PRIVATE')->private(),
                ];
            }
        };

        $result = (new CsvExporter(withBom: false))->export([
            ['NAME' => 'one', 'SECRET' => 'hidden', 'PRIVATE' => 'private'],
        ], new ExportContext($resource));

        self::assertTrue($result->isSuccess());
        self::assertSame("Name,Computed\nONE,one-computed\n", $result->content);
    }

    public function testItEscapesCsvValuesSafely(): void
    {
        $result = (new CsvExporter(withBom: false))->export([
            ['NAME' => 'One, "quoted"'],
        ], new ExportContext(new ProductResource(), fields: [Text::make('Name', 'NAME')]));

        self::assertStringContainsString('"One, ""quoted"""', $result->content);
    }
}
