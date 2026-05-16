<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Grid\GroupedRowsBuilderGroupResource;
use PHPUnit\Framework\TestCase;

final class IndexPageGroupedRowsTest extends TestCase
{
    public function testResourceGroupingEnablesCollapsibleRows(): void
    {
        $resource = new class () extends ProductResource {
            public function indexGrouping(): ?IndexGrouping
            {
                return IndexGrouping::make()->resource(GroupedRowsBuilderGroupResource::class)->foreignKey('GROUP_ID');
            }
        };

        $page = new class ($resource) extends IndexPage {
            public function exposedGrid(): \MB\Bitrix\AdminKit\Grid\Grid
            {
                return $this->buildGrid();
            }
        };

        self::assertTrue($page->exposedGrid()->hasCollapsibleRows());
    }

    public function testNoGroupingLeavesCollapsibleRowsDisabled(): void
    {
        $page = new class (new ProductResource()) extends IndexPage {
            public function exposedGrid(): \MB\Bitrix\AdminKit\Grid\Grid
            {
                return $this->buildGrid();
            }
        };

        self::assertFalse($page->exposedGrid()->hasCollapsibleRows());
    }
}
