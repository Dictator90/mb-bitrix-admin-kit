<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ToolbarRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        $GLOBALS['APPLICATION'] = mb_admin_kit_application_mock();
        parent::tearDown();
    }

    public function testItRegistersFilterAndCreateButton(): void
    {
        Toolbar::reset();
        $GLOBALS['APPLICATION'] = new class () {
            public array $components = [];
            public function IncludeComponent(string $name, string $template, array $params): void
            {
                $this->components[] = [$name, $template, $params];
            }
        };
        $grid = new Grid('products', [], [TextFilter::make('Name', 'NAME')]);

        (new ToolbarRenderer())->render(new ProductResource(), $grid, '/admin/products.php?action=add');

        self::assertCount(1, Toolbar::$filters);
        self::assertCount(2, Toolbar::$buttons);
        self::assertSame('products_filter', Toolbar::$filters[0]['FILTER_ID']);
        self::assertSame('bitrix:ui.toolbar', $GLOBALS['APPLICATION']->components[0][0]);
    }

    public function testItCanBuildCreateButtonWithoutSidePanel(): void
    {
        $resource = new ProductResource();
        $js = (new ToolbarRenderer())->createButtonJs($resource, new Grid('products'), '/create');

        self::assertSame('window.location.href="/create";', $js);
    }
}
