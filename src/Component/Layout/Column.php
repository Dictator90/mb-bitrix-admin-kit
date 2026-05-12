<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Grid column with configurable span (1–12).
 *
 * Usage:
 *   Column::make([Text::make('Name', 'NAME')])->span(6)
 *   Column::make([Text::make('Email', 'EMAIL')])->span(6)->smSpan(12)
 */
class Column extends AbstractLayoutComponent
{
    protected int $span = 12;
    protected ?int $smSpan = null;
    protected ?int $offsetStart = null;

    /** @param array<int, mixed> $children */
    public static function make(array $children = []): static
    {
        return new static($children);
    }

    public function span(int $span): static
    {
        $this->span = max(1, min(12, $span));

        return $this;
    }

    /** Responsive span applied via container query / data attribute (handled in CSS if desired). */
    public function smSpan(int $span): static
    {
        $this->smSpan = max(1, min(12, $span));

        return $this;
    }

    public function offset(int $cols): static
    {
        $this->offsetStart = max(0, min(11, $cols));

        return $this;
    }

    public function render(): string
    {
        $gridColumn = $this->offsetStart !== null
            ? ($this->offsetStart + 1) . ' / span ' . $this->span
            : 'span ' . $this->span;

        $styleExtra = ['grid-column' => $gridColumn];

        $dataAttrs = '';
        if ($this->smSpan !== null) {
            $dataAttrs = ' data-sm-span="' . $this->smSpan . '"';
        }

        $class = $this->buildClassAttr();
        $style = $this->buildStyleAttr($styleExtra);
        $attrs = $this->buildExtraAttrs();

        return "<div{$class}{$style}{$attrs}{$dataAttrs}>{$this->renderChildren()}</div>";
    }
}
