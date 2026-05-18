<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget;

use Bitrix\Main\ORM\Data\DataManager;
use Throwable;

/**
 * Stat-card widget that displays a record count from a Bitrix ORM DataManager table.
 *
 * Usage:
 *   CountWidget::make('Products', ProductTable::class)
 *   CountWidget::make('New orders', OrderTable::class)
 *       ->filter(['STATUS' => 'new'])
 *       ->color('warning')
 *       ->href('/bitrix/admin/vendor_orders.php')
 *       ->icon('--cart')
 *
 * Custom value (bypasses ORM):
 *   CountWidget::make('Revenue', OrderTable::class)
 *       ->value(fn() => '$ ' . number_format(OrderTable::calcRevenue()))
 */
final class CountWidget extends AbstractWidget
{
    /** @var class-string<DataManager> Bitrix ORM DataManager subclass. */
    private string $tableClass;

    /** @var array<string, mixed> */
    private array $filter = [];

    /** @var 'primary'|'success'|'danger'|'warning'|null */
    private ?string $color = null;

    private ?string $href = null;

    /** @var (\Closure(): (int|string))|null */
    private ?\Closure $valueCallback = null;

    /**
     * @param class-string<DataManager> $tableClass Bitrix ORM DataManager subclass.
     */
    public static function make(string $label, string $tableClass): static
    {
        $widget             = new static();
        $widget->label      = $label;
        $widget->tableClass = $tableClass;

        return $widget;
    }

    /** @param array<string, mixed> $filter ORM-style filter passed to getCount(). */
    public function filter(array $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    /** Accent colour for the displayed value: 'primary', 'success', 'danger', 'warning'. */
    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /** Make the whole card a clickable link. */
    public function href(string $url): static
    {
        $this->href = $url;

        return $this;
    }

    /**
     * Supply a custom value instead of an ORM count.
     * The closure may return an int or a pre-formatted string (e.g. '$ 1,234').
     *
     * @param \Closure(): (int|string) $fn
     */
    public function value(\Closure $fn): static
    {
        $this->valueCallback = $fn;

        return $this;
    }

    private function resolveValue(): string
    {
        if ($this->valueCallback !== null) {
            return (string)($this->valueCallback)();
        }

        if (!class_exists($this->tableClass)) {
            return '—';
        }

        try {
            return (string)($this->tableClass::getCount($this->filter));
        } catch (Throwable) {
            return '—';
        }
    }

    protected function renderWidget(): string
    {
        $count    = htmlspecialcharsbx($this->resolveValue());
        $label    = htmlspecialcharsbx($this->label);
        $colorCls = $this->color !== null
            ? ' adminkit-widget--' . htmlspecialcharsbx($this->color)
            : '';

        $iconHtml = '';
        if ($this->icon !== null) {
            $ic       = htmlspecialcharsbx($this->icon);
            $iconHtml = "<span class=\"adminkit-widget__icon ui-icon-set {$ic}\"></span>";
        }

        $inner = <<<HTML
<div class="adminkit-widget__stat{$colorCls}">
    {$iconHtml}<span class="adminkit-widget__value">{$count}</span>
    <span class="adminkit-widget__label">{$label}</span>
</div>
HTML;

        if ($this->href !== null) {
            $href = htmlspecialcharsbx($this->href);

            return "<a href=\"{$href}\" class=\"adminkit-widget__link\">{$inner}</a>";
        }

        return $inner;
    }
}
