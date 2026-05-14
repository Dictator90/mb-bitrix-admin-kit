<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent;
use MB\Bitrix\AdminKit\Support\DataWrapper;

/**
 * Inline alert block — rendered as static HTML, no JS required.
 *
 * Usage:
 *   Alert::make('Required fields are missing', 'danger')
 *   Alert::make('Saved successfully', Alert::SUCCESS)
 *   Alert::make()->info('Note: this will override existing data')
 */
class Alert extends AbstractLayoutComponent
{
    public const SUCCESS = 'success';
    public const DANGER = 'danger';
    public const WARNING = 'warning';
    public const INFO = 'info';

    protected string $message;
    protected string $type;
    protected bool $closable = false;
    protected ?string $icon = null;
    protected bool $rawHtml = false;

    public function __construct(string $message = '', string $type = self::INFO)
    {
        parent::__construct([]);
        $this->message = $message;
        $this->type = $type;
    }

    public static function make(string $message = '', string $type = self::INFO): static
    {
        return new static($message, $type);
    }

    public static function success(string $message): static
    {
        return new static($message, static::SUCCESS);
    }

    public static function danger(string $message): static
    {
        return new static($message, static::DANGER);
    }

    public static function warning(string $message): static
    {
        return new static($message, static::WARNING);
    }

    public static function info(string $message): static
    {
        return new static($message, static::INFO);
    }

    /**
     * Allow the message to contain raw HTML (links, bold, etc.).
     * Only use this with developer-controlled strings, never with user input.
     */
    public function html(bool $raw = true): static
    {
        $this->rawHtml = $raw;

        return $this;
    }

    public function closable(bool $closable = true): static
    {
        $this->closable = $closable;

        return $this;
    }

    public function icon(string $iconClass): static
    {
        $this->icon = $iconClass;

        return $this;
    }

    public function withItem(?DataWrapper $item): static
    {
        return $this;
    }

    public function render(): string
    {
        $cssType = match ($this->type) {
            static::SUCCESS => 'ui-alert-success',
            static::DANGER => 'ui-alert-danger',
            static::WARNING => 'ui-alert-warning',
            default => 'ui-alert-info',
        };

        $class = $this->buildClassAttr(['ui-alert', $cssType]);
        $style = $this->buildStyleAttr();
        $attrs = $this->buildExtraAttrs();

        $message = $this->rawHtml ? $this->message : htmlspecialcharsbx($this->message);

        $iconHtml = '';
        if ($this->icon) {
            $iconClass = htmlspecialcharsbx($this->icon);
            $iconHtml = "<span class=\"ui-icon-set {$iconClass}\" style=\"margin-right:8px;\"></span>";
        }

        $closeHtml = '';
        if ($this->closable) {
            $closeHtml = '<span class="ui-alert-close-btn" onclick="this.closest(\'.ui-alert\').remove()" style="cursor:pointer;float:right;font-size:16px;line-height:1;">&times;</span>';
        }

        return "<div{$class}{$style}{$attrs}>{$closeHtml}<span class=\"ui-alert-message\">{$iconHtml}{$message}</span></div>";
    }

    /** @return never[] */
    public function extractFields(): array
    {
        return [];
    }
}
