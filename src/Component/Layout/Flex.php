<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Flexbox row container.
 *
 * Usage:
 *   Flex::make([Text::make('Name', 'NAME'), Text::make('Email', 'EMAIL')])
 *       ->gap(16)->justify('between')->align('start')
 */
class Flex extends AbstractLayoutComponent
{
    protected string $justify = 'start';
    protected string $align = 'start';
    protected int $gap = 8;
    protected bool $wrap = true;
    protected string $direction = 'row';

    /** @param array<int, mixed> $children */
    public static function make(array $children = []): static
    {
        return new static($children);
    }

    public function justify(string $justify): static
    {
        $this->justify = $justify;

        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    public function gap(int $px): static
    {
        $this->gap = $px;

        return $this;
    }

    public function nowrap(): static
    {
        $this->wrap = false;

        return $this;
    }

    public function column(): static
    {
        $this->direction = 'column';

        return $this;
    }

    public function render(): string
    {
        $justify = match ($this->justify) {
            'end' => 'flex-end',
            'center' => 'center',
            'between' => 'space-between',
            'around' => 'space-around',
            'evenly' => 'space-evenly',
            default => 'flex-start',
        };

        $align = match ($this->align) {
            'end' => 'flex-end',
            'center' => 'center',
            'stretch' => 'stretch',
            'baseline' => 'baseline',
            default => 'flex-start',
        };

        $styleExtra = [
            'display' => 'flex',
            'flex-direction' => $this->direction,
            'flex-wrap' => $this->wrap ? 'wrap' : 'nowrap',
            'justify-content' => $justify,
            'align-items' => $align,
            'gap' => $this->gap . 'px',
        ];

        $class = $this->buildClassAttr();
        $style = $this->buildStyleAttr($styleExtra);
        $attrs = $this->buildExtraAttrs();

        return "<div{$class}{$style}{$attrs}>{$this->renderChildren()}</div>";
    }
}
