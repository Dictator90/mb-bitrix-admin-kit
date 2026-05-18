<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class DisableTotalCountTest extends TestCase
{
    public function testTotalCountCanBeDisabled(): void
    {
        ProductTable::reset();
        $resource = new class () extends ProductResource {
            public function useTotalCount(GridContext $context): bool
            {
                return false;
            }
        };
        $page = new class ($resource) extends IndexPage {
            public function run(Grid $grid): void
            {
                $this->loadData($grid);
            }
        };
        $grid = new Grid($resource->getGridId(), iterator_to_array($resource->indexFields()), [], [], '', 'ID');

        $page->run($grid);

        self::assertSame(0, ProductTable::$countCalls);
    }
}
