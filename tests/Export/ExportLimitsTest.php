<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Export;

use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ExportLimitsTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [
            ['ID' => 1, 'NAME' => 'One'],
            ['ID' => 2, 'NAME' => 'Two'],
            ['ID' => 3, 'NAME' => 'Three'],
        ];
    }

    public function testExportAllIsDisabledByDefault(): void
    {
        $result = ExportAction::make()->execute(new ExportContext(new ProductResource()));

        self::assertFalse($result->isSuccess());
        self::assertStringContainsString('Exporting all records', $result->errors[0] ?? '');
    }

    public function testExportAllIsAllowedWhenResourceAllowsIt(): void
    {
        $resource = new class () extends ProductResource {
            public function allowExportAll(): bool
            {
                return true;
            }
        };

        $result = ExportAction::make()->execute(new ExportContext($resource));

        self::assertTrue($result->isSuccess());
        self::assertStringContainsString('One', $result->content);
        self::assertStringContainsString('Three', $result->content);
    }

    public function testExportByFilterIsDisabledWhenResourceDisallowsIt(): void
    {
        $resource = new class () extends ProductResource {
            public function allowExportByFilter(): bool
            {
                return false;
            }
        };

        $result = ExportAction::make()->execute(new ExportContext($resource, filter: ['NAME' => 'One']));

        self::assertFalse($result->isSuccess());
        self::assertStringContainsString('Export by filter is disabled', $result->errors[0] ?? '');
    }

    public function testSelectedExportWorksWithoutExplicitFilter(): void
    {
        $result = ExportAction::make()->execute(new ExportContext(new ProductResource(), selectedIds: [2, 3]));

        self::assertTrue($result->isSuccess());
        self::assertStringContainsString('Two', $result->content);
        self::assertStringContainsString('Three', $result->content);
        self::assertStringNotContainsString('One', $result->content);
    }

    public function testMaxExportRowsBlocksLargeSelectedExport(): void
    {
        $resource = new LimitedExportResource();
        ProductTable::$countCalls = 0;
        ProductTable::$listCalls = 0;

        $result = ExportAction::make()->execute(new ExportContext($resource, selectedIds: [1, 2, 3]));

        self::assertFalse($result->isSuccess());
        $error = $result->errors[0] ?? '';
        self::assertTrue(str_contains($error, 'Maximum: 2') || str_contains($error, 'Максимум: 2'));
        self::assertSame(0, ProductTable::$listCalls);
    }

    public function testMaxExportRowsBlocksLargeFilterExportUsingCountBeforeGetList(): void
    {
        $resource = new AllLimitedExportResource();
        ProductTable::$countCalls = 0;
        ProductTable::$listCalls = 0;

        $result = ExportAction::make()->execute(new ExportContext($resource));

        self::assertFalse($result->isSuccess());
        self::assertSame(1, ProductTable::$countCalls);
        self::assertSame(0, ProductTable::$listCalls);
    }

    public function testMaxExportRowsZeroDisablesExport(): void
    {
        $resource = new class () extends ProductResource {
            public function maxExportRows(): int
            {
                return 0;
            }
        };

        $result = ExportAction::make()->execute(new ExportContext($resource, selectedIds: [1]));

        self::assertFalse($result->isSuccess());
        self::assertSame(0, ProductTable::$listCalls);
    }

    public function testFilterExportWithinLimitUsesCountThenGetList(): void
    {
        $resource = new class () extends ProductResource {
            public function maxExportRows(): int
            {
                return 10;
            }
        };
        ProductTable::$countCalls = 0;
        ProductTable::$listCalls = 0;

        $result = ExportAction::make()->execute(new ExportContext($resource, filter: ['NAME' => 'One']));

        self::assertTrue($result->isSuccess());
        self::assertSame(1, ProductTable::$countCalls);
        self::assertSame(1, ProductTable::$listCalls);
    }
}

class LimitedExportResource extends ProductResource
{
    public function maxExportRows(): int
    {
        return 2;
    }
}

final class AllLimitedExportResource extends LimitedExportResource
{
    public function allowExportAll(): bool
    {
        return true;
    }
}
