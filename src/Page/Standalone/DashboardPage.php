<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Widget\AbstractWidget;

/**
 * Dashboard page skeleton for module overview pages.
 *
 * Override widgets() to return an iterable of widget objects, layout
 * components, or raw HTML strings. The dashboard renders items in a
 * 12-column CSS grid — each AbstractWidget spans 3 columns by default
 * (four per row); use ->span(N) to change the column span.
 *
 * Full layout composition is supported: any ComponentContract object
 * (Grid, Column, Box, Flex, Tabs, Alert, …) may appear in the list.
 * Add ->style('grid-column', 'span 12') on layout components that should
 * span the full width.
 *
 * Usage:
 *   use MB\Bitrix\AdminKit\Widget\CountWidget;
 *   use MB\Bitrix\AdminKit\Widget\GraphWidget;
 *
 *   protected function widgets(): iterable
 *   {
 *       return [
 *           CountWidget::make('Products', ProductTable::class),
 *           CountWidget::make('Orders', OrderTable::class)->color('warning'),
 *           GraphWidget::make('Sales', 'serial')->span(12)->data([…]),
 *       ];
 *   }
 */
abstract class DashboardPage extends CustomPage
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->pageType = PageType::DASHBOARD;
    }

    protected function pageTitle(): string
    {
        return $this->title();
    }

    protected function content(): string
    {
        // Materialise once — safe for both plain arrays and generators.
        $widgetList = AdminCollection::make($this->widgets())->all();

        // Collect and load Bitrix extensions required by widgets (e.g. main.amcharts.3.11).
        $extensions = [];
        foreach ($widgetList as $widget) {
            if ($widget instanceof AbstractWidget) {
                $extensions = array_merge($extensions, $widget->getRequiredExtensions());
            }
        }
        if ($extensions !== []) {
            (new AssetManager())->addExtensions(array_unique($extensions))->load();
        }

        $html  = $this->renderWidgetCss();
        $html .= '<div class="adminkit-dashboard" style="display:grid;grid-template-columns:repeat(12,1fr);gap:16px;">';

        foreach ($widgetList as $widget) {
            if ($widget instanceof ComponentContract) {
                $html .= $widget->render();
            } else {
                // Backward compat: raw HTML strings are wrapped in a 3-column cell.
                $html .= '<div class="adminkit-dashboard__widget" style="grid-column:span 3;">'
                    . (string)$widget
                    . '</div>';
            }
        }

        return $html . '</div>';
    }

    /**
     * Return widgets, layout components, or raw HTML strings to display.
     *
     * @return iterable<string|ComponentContract>
     */
    protected function widgets(): iterable
    {
        return [];
    }

    /**
     * Inline CSS for widget cards.
     * Inlined to avoid a hard dependency on a Bitrix extension for the CSS bundle.
     */
    protected function renderWidgetCss(): string
    {
        return <<<'CSS'
<style>
.adminkit-widget{background:#fff;border:1px solid #dfe0e5;border-radius:8px;padding:20px;box-sizing:border-box;min-height:100px;}
.adminkit-widget__stat{display:flex;flex-direction:column;gap:6px;}
.adminkit-widget__value{font-size:32px;font-weight:700;line-height:1;color:#333;}
.adminkit-widget__label{font-size:13px;color:#828282;}
.adminkit-widget__icon{font-size:24px;margin-bottom:6px;display:block;}
.adminkit-widget__link{text-decoration:none;display:block;color:inherit;}
.adminkit-widget__link:hover .adminkit-widget{border-color:#2fc6f6;}
.adminkit-widget__header{margin-bottom:8px;}
.adminkit-widget__title{font-size:14px;font-weight:600;color:#333;}
.adminkit-widget--success .adminkit-widget__value{color:#5ea831;}
.adminkit-widget--danger .adminkit-widget__value{color:#e22402;}
.adminkit-widget--warning .adminkit-widget__value{color:#ee8516;}
.adminkit-widget--primary .adminkit-widget__value{color:#2fc6f6;}
</style>
CSS;
    }
}
