<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

/**
 * Custom HTML classes, styles, and attributes for the form field wrapper
 * and inner element. Inspired by MoonShine's customAttributes() / class() API.
 *
 * Render helpers ({@see renderWrapperAttributes()},
 * {@see renderElementAttributes()}) return a string fragment that begins with
 * a leading space and contains the `class="…"`, `style="…"`, and any extra
 * attribute pairs. The fragment is meant to be embedded directly inside the
 * opening tag, e.g. `<div{$wrapperAttrs}>`.
 *
 * Whenever `ui-ctl` appears in the wrapper base classes and {@see fullWidth()}
 * is enabled (default), `ui-ctl-w100` is added automatically.
 */
trait HasFieldAttributes
{
    /** @var list<string> */
    protected array $customClasses = [];

    /** @var list<string> */
    protected array $customWrapperClasses = [];

    protected string $customStyle = '';

    protected string $customWrapperStyle = '';

    /** @var array<string,string> */
    protected array $customAttrs = [];

    /** @var array<string,string> */
    protected array $customWrapperAttrs = [];

    protected bool $fullWidth = true;

    /**
     * Append CSS classes to the inner form control element
     * (the `<input>`, `<select>`, `<textarea>`).
     */
    public function class(string ...$classes): static
    {
        foreach ($classes as $class) {
            $this->appendClasses($this->customClasses, $class);
        }

        return $this;
    }

    /**
     * Append CSS classes to the form field wrapper (`.ui-ctl`).
     */
    public function wrapperClass(string ...$classes): static
    {
        foreach ($classes as $class) {
            $this->appendClasses($this->customWrapperClasses, $class);
        }

        return $this;
    }

    /**
     * Append an inline style declaration to the inner element.
     * Accepts an already-formatted CSS string ("color: red; margin: 0").
     */
    public function style(string $style): static
    {
        $this->customStyle = $this->mergeStyleString($this->customStyle, $style);

        return $this;
    }

    /**
     * Append an inline style declaration to the wrapper.
     */
    public function wrapperStyle(string $style): static
    {
        $this->customWrapperStyle = $this->mergeStyleString($this->customWrapperStyle, $style);

        return $this;
    }

    /**
     * Set arbitrary HTML attributes on the inner element.
     * `class` and `style` keys are merged via {@see class()} / {@see style()}.
     *
     * @param array<string,scalar|null> $attributes
     */
    public function customAttributes(array $attributes, bool $replace = false): static
    {
        if ($replace) {
            $this->customClasses = [];
            $this->customStyle = '';
            $this->customAttrs = [];
        }

        $this->mergeAttributes(
            $attributes,
            $this->customClasses,
            $this->customStyle,
            $this->customAttrs,
        );

        return $this;
    }

    /**
     * Set arbitrary HTML attributes on the wrapper.
     *
     * @param array<string,scalar|null> $attributes
     */
    public function customWrapperAttributes(array $attributes, bool $replace = false): static
    {
        if ($replace) {
            $this->customWrapperClasses = [];
            $this->customWrapperStyle = '';
            $this->customWrapperAttrs = [];
        }

        $this->mergeAttributes(
            $attributes,
            $this->customWrapperClasses,
            $this->customWrapperStyle,
            $this->customWrapperAttrs,
        );

        return $this;
    }

    /**
     * Toggle the automatic `ui-ctl-w100` class on `.ui-ctl` wrappers.
     */
    public function fullWidth(bool $enabled = true): static
    {
        $this->fullWidth = $enabled;

        return $this;
    }

    /** Shortcut for {@see fullWidth(false)}. */
    public function withoutFullWidth(): static
    {
        return $this->fullWidth(false);
    }

    public function isFullWidth(): bool
    {
        return $this->fullWidth;
    }

    /**
     * Build the wrapper attribute fragment for the opening `<div>` tag.
     * Pass the field-specific base classes ("ui-ctl", "ui-ctl-textbox"…).
     */
    public function renderWrapperAttributes(string ...$baseClasses): string
    {
        $classes = $baseClasses;
        if ($this->fullWidth && in_array('ui-ctl', $baseClasses, true)) {
            $classes[] = 'ui-ctl-w100';
        }

        foreach ($this->customWrapperClasses as $class) {
            $classes[] = $class;
        }

        return $this->buildAttributeFragment($classes, $this->customWrapperStyle, $this->customWrapperAttrs);
    }

    /**
     * Build the element attribute fragment for an `<input>`, `<select>`, etc.
     */
    public function renderElementAttributes(string ...$baseClasses): string
    {
        $classes = array_merge($baseClasses, $this->customClasses);

        return $this->buildAttributeFragment($classes, $this->customStyle, $this->customAttrs);
    }

    /**
     * @param list<string> $classes
     * @param array<string,string> $attrs
     */
    private function buildAttributeFragment(array $classes, string $style, array $attrs): string
    {
        $classes = array_values(array_unique(array_filter($classes, static fn (string $c): bool => $c !== '')));
        $fragment = '';
        if ($classes !== []) {
            $fragment .= ' class="' . htmlspecialcharsbx(implode(' ', $classes)) . '"';
        }

        $style = trim($style, "; \t\n\r\0\x0B");
        if ($style !== '') {
            $fragment .= ' style="' . htmlspecialcharsbx($style) . '"';
        }

        foreach ($attrs as $name => $value) {
            $fragment .= ' ' . $name . '="' . htmlspecialcharsbx($value) . '"';
        }

        return $fragment;
    }

    /** @param list<string> $bucket */
    private function appendClasses(array &$bucket, string $raw): void
    {
        $parts = preg_split('/\s+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($parts as $part) {
            if (!in_array($part, $bucket, true)) {
                $bucket[] = $part;
            }
        }
    }

    private function mergeStyleString(string $current, string $addition): string
    {
        $current = trim($current, "; \t\n\r\0\x0B");
        $addition = trim($addition, "; \t\n\r\0\x0B");

        if ($current === '') {
            return $addition;
        }
        if ($addition === '') {
            return $current;
        }

        return $current . '; ' . $addition;
    }

    /**
     * @param array<int|string,scalar|null> $attributes
     * @param list<string> $classes
     * @param array<string,string> $other
     */
    private function mergeAttributes(array $attributes, array &$classes, string &$style, array &$other): void
    {
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || $name === '' || $value === null) {
                continue;
            }

            $lowered = strtolower($name);
            $stringValue = is_bool($value) ? ($value ? $name : '') : (string) $value;

            if ($lowered === 'class') {
                $this->appendClasses($classes, $stringValue);
                continue;
            }

            if ($lowered === 'style') {
                $style = $this->mergeStyleString($style, $stringValue);
                continue;
            }

            if (!$this->isSafeAttributeName($name)) {
                continue;
            }

            $other[$name] = $stringValue;
        }
    }

    private function isSafeAttributeName(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:.\-]*$/', $name);
    }
}
