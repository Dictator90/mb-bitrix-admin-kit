<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Widget\Dashboard\DashboardRenderer;

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
        return (new DashboardRenderer())->render($this->widgets());
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

}
