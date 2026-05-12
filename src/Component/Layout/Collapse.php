<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Collapsible section using <details>/<summary> — no JS required.
 *
 * Usage:
 *   Collapse::make('Advanced settings', [
 *       Text::make('Api key', 'API_KEY'),
 *   ])->open()
 */
class Collapse extends AbstractLayoutComponent
{
    protected string $title;
    protected bool $open = false;

    /** @param array<int, mixed> $children */
    public function __construct(string $title, array $children = [])
    {
        parent::__construct($children);
        $this->title = $title;
    }

    /** @param array<int, mixed> $children */
    public static function make(string $title, array $children = []): static
    {
        return new static($title, $children);
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;

        return $this;
    }

    public function render(): string
    {
        $openAttr = $this->open ? ' open' : '';
        $title = htmlspecialcharsbx($this->title);
        $children = $this->renderChildren();
        $class = $this->buildClassAttr(['adminkit-collapse']);
        $style = $this->buildStyleAttr();
        $attrs = $this->buildExtraAttrs();

        return <<<HTML
        <details{$class}{$style}{$attrs}{$openAttr}>
            <summary class="adminkit-collapse__summary">{$title}</summary>
            <div class="adminkit-collapse__body">
                {$children}
            </div>
        </details>
        HTML;
    }
}
