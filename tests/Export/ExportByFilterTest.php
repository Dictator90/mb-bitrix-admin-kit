<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Export;

use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ExportByFilterTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'One'], ['ID' => 2, 'NAME' => 'Two']];
    }

    public function testItExportsRowsByExplicitFilter(): void
    {
        $result = ExportAction::make()->execute(new ExportContext(new ProductResource(), filter: ['NAME' => 'One']));

        self::assertTrue($result->isSuccess());
        self::assertStringContainsString('One', $result->content);
        self::assertStringNotContainsString('Two', $result->content);
    }

    public function testItDoesNotExportAllRowsByDefault(): void
    {
        $result = ExportAction::make()->execute(new ExportContext(new ProductResource()));

        self::assertFalse($result->isSuccess());
        self::assertSame(['Exporting all records is disabled by default. Select records or pass an explicit filter.'], $result->errors);
    }
}
