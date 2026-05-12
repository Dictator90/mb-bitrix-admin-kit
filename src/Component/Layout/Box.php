<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Bordered section box with an optional title.
 *
 * Usage:
 *   Box::make('Contact info', [
 *       Text::make('Phone', 'PHONE'),
 *       Email::make('Email', 'EMAIL'),
 *   ])->collapsible()
 */
class Box extends AbstractLayoutComponent
{
    protected ?string $title = null;
    protected bool $collapsible = false;
    protected bool $collapsed = false;

    /** @param array<int, mixed> $children */
    public function __construct(array|string $titleOrChildren = [], array $children = [])
    {
        if (is_string($titleOrChildren)) {
            $this->title = $titleOrChildren;
            parent::__construct($children);
        } else {
            parent::__construct($titleOrChildren);
        }
    }

    /** @param array<int, mixed> $children */
    public static function make(array|string $titleOrChildren = [], array $children = []): static
    {
        return new static($titleOrChildren, $children);
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function collapsible(bool $collapsed = false): static
    {
        $this->collapsible = true;
        $this->collapsed = $collapsed;

        return $this;
    }

    public function render(): string
    {
        $children = $this->renderChildren();
        $class = $this->buildClassAttr(['adminkit-box']);
        $style = $this->buildStyleAttr();
        $attrs = $this->buildExtraAttrs();

        $titleHtml = '';
        if ($this->title !== null) {
            $title = htmlspecialcharsbx($this->title);

            if ($this->collapsible) {
                $collapsed = $this->collapsed ? 'true' : 'false';
                $bodyClass = $this->collapsed ? 'adminkit-box__body adminkit-box__body--collapsed' : 'adminkit-box__body';
                $titleHtml = <<<HTML
                <div class="adminkit-box__title adminkit-box__title--collapsible" data-collapsed="{$collapsed}" onclick="
                    var t=this, b=t.nextElementSibling;
                    var collapsed = b.classList.toggle('adminkit-box__body--collapsed');
                    t.dataset.collapsed = collapsed ? 'true' : 'false';
                ">
                    <span class="adminkit-box__title-text">{$title}</span>
                    <span class="adminkit-box__toggle-icon"></span>
                </div>
                <div class="{$bodyClass}">
                HTML;
            } else {
                $titleHtml = "<div class=\"adminkit-box__title\">{$title}</div><div class=\"adminkit-box__body\">";
            }
        } else {
            $titleHtml = '<div class="adminkit-box__body">';
        }

        return <<<HTML
        <div{$class}{$style}{$attrs}>
            {$titleHtml}
                {$children}
            </div>
        </div>
        HTML;
    }
}
