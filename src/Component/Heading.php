<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent;
use MB\Bitrix\AdminKit\Support\DataWrapper;

/**
 * Section heading (h2–h6).
 *
 * Usage:
 *   Heading::make('Personal info')
 *   Heading::make('Settings', 3)->subtitle('Configure your preferences')
 */
class Heading extends AbstractLayoutComponent
{
    protected string $text;
    protected int $level;
    protected ?string $subtitle = null;

    public function __construct(string $text, int $level = 2)
    {
        parent::__construct([]);
        $this->text = $text;
        $this->level = max(2, min(6, $level));
    }

    public static function make(string $text, int $level = 2): static
    {
        return new static($text, $level);
    }

    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function level(int $level): static
    {
        $this->level = max(2, min(6, $level));

        return $this;
    }

    public function withItem(?DataWrapper $item): static
    {
        return $this;
    }

    public function render(): string
    {
        $tag = 'h' . $this->level;
        $text = htmlspecialcharsbx($this->text);

        $fontSize = match ($this->level) {
            2 => '20px',
            3 => '17px',
            4 => '15px',
            default => '14px',
        };

        $class = $this->buildClassAttr(['adminkit-heading']);
        $style = $this->buildStyleAttr([
            'font-size'    => $fontSize,
            'font-weight'  => 'var(--ui-font-weight-medium, 500)',
            'color'        => 'var(--ui-color-base-90, #1f2733)',
            'margin'       => '0 0 8px 0',
            'padding'      => '0',
        ]);
        $attrs = $this->buildExtraAttrs();

        $subtitleHtml = '';
        if ($this->subtitle !== null) {
            $sub = htmlspecialcharsbx($this->subtitle);
            $subtitleHtml = "<p style=\"margin:4px 0 0 0;color:var(--ui-color-base-50, #8a939e);font-size:13px;\">{$sub}</p>";
        }

        return "<{$tag}{$class}{$style}{$attrs}>{$text}</{$tag}>{$subtitleHtml}";
    }

    /** @return never[] */
    public function extractFields(): array
    {
        return [];
    }
}
