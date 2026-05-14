<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Export;

use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ExportSelectedRowsTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        ProductTable::$rows = [['ID' => 1, 'NAME' => 'One'], ['ID' => 2, 'NAME' => 'Two']];
    }

    public function testItExportsOnlySelectedRows(): void
    {
        $result = ExportAction::make()->execute(new ExportContext(new ProductResource(), selectedIds: [2]));

        self::assertTrue($result->isSuccess());
        self::assertStringContainsString('Two', $result->content);
        self::assertStringNotContainsString('One', $result->content);
        self::assertSame(['ID' => [2]], ProductTable::$lastParams['filter']);
    }
}
