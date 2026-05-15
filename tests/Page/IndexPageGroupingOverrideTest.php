<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Grid\GroupedRowsBuilderGroupResource;
use PHPUnit\Framework\TestCase;

final class IndexPageGroupingOverrideTest extends TestCase
{
    public function testCustomIndexPageGroupingOverridesResource(): void
    {
        $resource = new class () extends ProductResource {
            public function indexGrouping(): ?IndexGrouping
            {
                return null;
            }
        };
        $page = new class ($resource) extends IndexPage {
            protected function grouping(): ?IndexGrouping
            {
                return IndexGrouping::make()->resource(GroupedRowsBuilderGroupResource::class)->foreignKey('GROUP_ID');
            }

            public function exposedGrouping(): ?IndexGrouping
            {
                return $this->definition()->grouping();
            }

            public function exposedGrid(): \MB\Bitrix\AdminKit\Grid\Grid
            {
                return $this->buildGrid();
            }
        };

        self::assertInstanceOf(IndexGrouping::class, $page->exposedGrouping());
        self::assertTrue($page->exposedGrid()->hasCollapsibleRows());
    }

    public function testCustomIndexPageCanDisableResourceGrouping(): void
    {
        $resource = new class () extends ProductResource {
            public function indexGrouping(): ?IndexGrouping
            {
                return IndexGrouping::make()->resource(GroupedRowsBuilderGroupResource::class)->foreignKey('GROUP_ID');
            }
        };
        $page = new class ($resource) extends IndexPage {
            protected function grouping(): ?IndexGrouping
            {
                return null;
            }

            public function exposedGrid(): \MB\Bitrix\AdminKit\Grid\Grid
            {
                return $this->buildGrid();
            }
        };

        self::assertFalse($page->exposedGrid()->hasCollapsibleRows());
    }
}
