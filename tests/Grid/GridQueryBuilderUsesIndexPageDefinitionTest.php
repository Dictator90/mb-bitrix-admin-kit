<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderUsesIndexPageDefinitionTest extends TestCase
{
    public function testSelectComesFromIndexPageDefinition(): void
    {
        $resource = new ProductResource();
        $page = new class ($resource) extends IndexPage {
            protected function fields(): iterable
            {
                return [Text::make('Page field', 'PAGE_FIELD')];
            }

            protected function defaultSelect(): array
            {
                return ['PAGE_DEFAULT'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource), $page->definition());

        self::assertSame(['PAGE_FIELD', 'PAGE_DEFAULT', 'ID'], $params['select']);
    }
}
