<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use Bitrix\UI\Buttons\Size;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ToolbarRendererTest extends TestCase
{
    public function testItRegistersFilterAndCreateButton(): void
    {
        $GLOBALS['APPLICATION'] = new class () {
            public array $components = [];
            public function IncludeComponent(string $name, string $template, array $params): void
            {
                $this->components[] = [$name, $template, $params];
            }
        };
        $grid = new Grid('products', [], [TextFilter::make('Name', 'NAME')]);

        (new ToolbarRenderer())->render(new ProductResource(), $grid, '/admin/products.php?action=add');

        self::assertSame('products_filter', $grid->getFilterId());
        self::assertNotEmpty($grid->getFilterComponentParams());
        self::assertContains('bitrix:ui.toolbar', array_column($GLOBALS['APPLICATION']->components, 0));
    }

    public function testItCanBuildCreateButtonWithoutSidePanel(): void
    {
        $resource = new ProductResource();
        $js = (new ToolbarRenderer())->createButtonJs($resource, new Grid('products'), '/create');

        self::assertSame('window.location.href="/create";', $js);
    }

    public function testItAppliesToolbarFeatureHooks(): void
    {
        $GLOBALS['APPLICATION'] = new class () {
            public function IncludeComponent(string $name, string $template, array $params): void
            {
            }
        };

        $marker = 'adminkit-after-' . uniqid('', false);
        $resource = new class ($marker) extends ProductResource {
            public function __construct(private string $marker)
            {
            }

            public function toolbarFavoriteStar(): bool
            {
                return true;
            }

            public function toolbarAfterTitleHtml(): ?string
            {
                return '<span>' . $this->marker . '</span>';
            }

            public function toolbarCopyLink(): ?array
            {
                return ['link' => '/copy-me'];
            }
        };

        (new ToolbarRenderer())->render($resource, new Grid('products_features'), '/admin/products.php?action=add');

        self::assertTrue(Toolbar::hasFavoriteStar());
        self::assertStringContainsString($marker, (string)Toolbar::getAfterTitleHtml());
        self::assertNotEmpty(Toolbar::getCopyLinkButton());
    }

    public function testItRendersButtonOptionActionWithoutError(): void
    {
        $GLOBALS['APPLICATION'] = new class () {
            public array $components = [];
            public function IncludeComponent(string $name, string $template, array $params): void
            {
                $this->components[] = $name;
            }
        };

        $resource = new class () extends ProductResource {
            public function toolbarActions(): iterable
            {
                return [
                    ToolbarAction::make('Действие', 'act')
                        ->counter(5)
                        ->size(Size::SMALL)
                        ->disabled()
                        ->round(),
                ];
            }
        };

        (new ToolbarRenderer())->render($resource, new Grid('products_btn'), '/admin/products.php?action=add');

        self::assertContains('bitrix:ui.toolbar', $GLOBALS['APPLICATION']->components);
    }
}
