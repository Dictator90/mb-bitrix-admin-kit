<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent;
use MB\Bitrix\AdminKit\Support\DataWrapper;

/**
 * Small status badge / tag.
 *
 * Usage:
 *   Badge::make('Active', 'success')
 *   Badge::make('Draft')->neutral()
 *   Badge::make($status)->map(['Y' => 'success', 'N' => 'danger'])
 */
class Badge extends AbstractLayoutComponent
{
    public const SUCCESS = 'success';
    public const DANGER  = 'danger';
    public const WARNING = 'warning';
    public const INFO    = 'info';
    public const NEUTRAL = 'neutral';

    protected string $text;
    protected string $type;
    /** @var array<string, string> */
    protected array $colorMap = [];
    protected bool $pill = false;

    public function __construct(string $text = '', string $type = self::NEUTRAL)
    {
        parent::__construct([]);
        $this->text = $text;
        $this->type = $type;
    }

    public static function make(string $text = '', string $type = self::NEUTRAL): static
    {
        return new static($text, $type);
    }

    public function success(): static
    {
        $this->type = static::SUCCESS;

        return $this;
    }

    public function danger(): static
    {
        $this->type = static::DANGER;

        return $this;
    }

    public function warning(): static
    {
        $this->type = static::WARNING;

        return $this;
    }

    public function info(): static
    {
        $this->type = static::INFO;

        return $this;
    }

    public function neutral(): static
    {
        $this->type = static::NEUTRAL;

        return $this;
    }

    /** @param array<string, string> $map  value => badge type */
    public function map(array $map): static
    {
        $this->colorMap = $map;

        return $this;
    }

    public function pill(): static
    {
        $this->pill = true;

        return $this;
    }

    public function withItem(?DataWrapper $item): static
    {
        return $this;
    }

    public function render(): string
    {
        $type = $this->colorMap[$this->text] ?? $this->type;

        [$bg, $color] = match ($type) {
            static::SUCCESS => ['#d5f1e2', '#1a7f4b'],
            static::DANGER  => ['#fde8e8', '#c0392b'],
            static::WARNING => ['#fff3cd', '#856404'],
            static::INFO    => ['#d1ecf1', '#0c5460'],
            default         => ['var(--ui-color-base-10, #eef2f4)', 'var(--ui-color-base-60, #7a8592)'],
        };

        $radius = $this->pill ? '100px' : 'var(--ui-border-radius-sm, 3px)';
        $text = htmlspecialcharsbx($this->text);

        $class = $this->buildClassAttr(['adminkit-badge']);
        $style = $this->buildStyleAttr([
            'display'       => 'inline-block',
            'padding'       => '2px 10px',
            'border-radius' => $radius,
            'background'    => $bg,
            'color'         => $color,
            'font-size'     => '12px',
            'font-weight'   => 'var(--ui-font-weight-medium, 500)',
            'white-space'   => 'nowrap',
        ]);
        $attrs = $this->buildExtraAttrs();

        return "<span{$class}{$style}{$attrs}>{$text}</span>";
    }

    /** @return never[] */
    public function extractFields(): array
    {
        return [];
    }
}
