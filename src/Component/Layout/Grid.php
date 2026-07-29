<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * 12-column CSS grid container.
 *
 * Usage:
 *   Grid::make([
 *       Column::make([Text::make('Name', 'NAME')])->span(6),
 *       Column::make([Text::make('Email', 'EMAIL')])->span(6),
 *   ])->gap(16)
 */
class Grid extends AbstractLayoutComponent
{
    protected int $gap = 16;
    protected int $columns = 12;

    /** @param array<int, mixed> $children */
    public static function make(array $children = []): static
    {
        return new static($children);
    }

    public function gap(int $px): static
    {
        $this->gap = $px;

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function render(): string
    {
        $class = $this->buildClassAttr(['ui-form-row']);
        $style = $this->buildStyleAttr([
            'display' => 'grid',
            'grid-template-columns' => "repeat({$this->columns}, 1fr)",
            'gap' => $this->gap . 'px',
        ]);
        $attrs = $this->buildExtraAttrs();

        return "<div{$class}{$style}{$attrs}>{$this->renderChildren()}</div>";
    }
}
