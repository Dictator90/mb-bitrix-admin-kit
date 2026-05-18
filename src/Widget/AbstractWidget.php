<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget;

use MB\Bitrix\AdminKit\Component\Concerns\HasConditionalVisibility;
use MB\Bitrix\AdminKit\Component\Concerns\HasHtmlAttributes;
use MB\Bitrix\AdminKit\Contracts\Widget\DashboardWidgetContract;

/**
 * Base class for dashboard widgets.
 *
 * Widgets are leaf-node components that render a self-contained card.
 * They extend AbstractLayoutComponent, so they can be composed inside
 * Grid, Column, Box, and any other layout component, as well as used
 * directly inside DashboardPage::widgets().
 *
 * @see \MB\Bitrix\AdminKit\Page\Standalone\DashboardPage
 */
abstract class AbstractWidget implements DashboardWidgetContract
{
    use HasHtmlAttributes;
    use HasConditionalVisibility;

    protected string $label  = '';
    protected ?string $icon  = null;
    protected int $span      = 3;  // default: 3 of 12 dashboard grid columns

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** CSS class for a Bitrix icon-set icon, e.g. '--cart'. */
    public function icon(string $cssClass): static
    {
        $this->icon = $cssClass;

        return $this;
    }

    /**
     * Set how many of the dashboard's 12 columns this widget spans (1–12).
     * Default: 3 (four widgets per row).
     */
    public function span(int $columns): static
    {
        $this->span = max(1, min(12, $columns));

        return $this;
    }

    /**
     * Bitrix extension IDs required by this widget.
     * DashboardPage collects and loads them automatically before rendering.
     *
     * @return list<string>
     */
    public function getRequiredExtensions(): array
    {
        return [];
    }

    abstract protected function renderWidget(): string;

    public function render(): string
    {
        $class = $this->buildClassAttr(['adminkit-widget']);

        // Apply grid-column span as a default only when the caller has not
        // already set it via ->style('grid-column', '…').
        $styleExtra = [];
        if (!array_key_exists('grid-column', $this->styles)) {
            $styleExtra['grid-column'] = 'span ' . $this->span;
        }

        $style = $this->buildStyleAttr($styleExtra);
        $attrs = $this->buildExtraAttrs();

        return "<div{$class}{$style}{$attrs}>{$this->renderWidget()}</div>";
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
