<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Horizontal divider, optionally with a label.
 *
 * Usage:
 *   Divider::make()
 *   Divider::make('Or')
 */
class Divider extends AbstractLayoutComponent
{
    protected ?string $label = null;

    public function __construct(string $label = '')
    {
        parent::__construct([]);
        $this->label = $label !== '' ? $label : null;
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function render(): string
    {
        $class = $this->buildClassAttr(['adminkit-divider', 'adminkit-divider__line']);
        $style = $this->buildStyleAttr();
        $attrs = $this->buildExtraAttrs();

        if ($this->label === null) {
            return "<hr{$class}{$style}{$attrs}>";
        }

        $label = htmlspecialcharsbx($this->label);
        $class = $this->buildClassAttr(['adminkit-divider', 'adminkit-divider--labeled']);

        return <<<HTML
        <div{$class}{$style}{$attrs}>
            <div class="adminkit-divider__line"></div>
            <span class="adminkit-divider__label">{$label}</span>
            <div class="adminkit-divider__line"></div>
        </div>
        HTML;
    }

    /** @return never[] */
    public function extractFields(): array
    {
        return [];
    }
}
