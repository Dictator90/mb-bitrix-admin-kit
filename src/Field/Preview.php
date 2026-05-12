<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;

/**
 * Read-only computed display field — AdminKit analog of MoonShine's Preview.
 *
 * Renders a value without an input — never submitted to the server.
 * Combine with format() or the built-in badge() / link() helpers.
 *
 * Usage:
 *   Preview::make('Статус', 'STATUS')
 *       ->badge('success')
 *
 *   Preview::make('Ссылка', 'DETAIL_URL')
 *       ->link()
 *
 *   Preview::make('HTML', 'COMPUTED')
 *       ->format(fn($v) => '<b>' . htmlspecialcharsbx($v) . '</b>')
 */
class Preview extends Field
{
    protected ?string $badgeColor = null;
    protected bool $asLink = false;
    protected ?string $linkTarget = '_blank';

    // ── Badge ────────────────────────────────────────────────────────────

    /**
     * Wrap the value in a ui-label badge.
     *
     * $color: 'success' | 'danger' | 'warning' | 'info' | 'default'
     *         or any raw Bitrix ui-label-* suffix.
     */
    public function badge(string $color = 'default'): static
    {
        $this->badgeColor = $color;

        return $this;
    }

    // ── Link ─────────────────────────────────────────────────────────────

    /**
     * Render the value as an anchor tag.
     * The value itself is used as the href.
     */
    public function link(string $target = '_blank'): static
    {
        $this->asLink = true;
        $this->linkTarget = $target;

        return $this;
    }

    // ── FieldContract ────────────────────────────────────────────────────

    public function isReadOnly(): bool
    {
        return true;
    }

    public function renderFormField(mixed $value = null): string
    {
        return '<div class="adminkit-preview">' . $this->previewValue($this->resolveValue($value)) . '</div>';
    }

    public function previewValue(mixed $value): string
    {
        // format() / preview() closures from HasFormat take precedence
        if ($this->formatter !== null) {
            return (string)($this->formatter)($value);
        }
        if ($this->preview !== null) {
            return (string)($this->preview)($value);
        }

        $text = htmlspecialcharsbx((string)($value ?? ''));

        if ($text === '') {
            return '<span class="adminkit-preview__empty">—</span>';
        }

        if ($this->asLink) {
            return $this->renderLink($text);
        }

        if ($this->badgeColor !== null) {
            return $this->renderBadge($text);
        }

        return $text;
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function renderBadge(string $text): string
    {
        Extension::load('ui.label');

        static $map = [
            'success' => 'ui-label-success',
            'danger'  => 'ui-label-red',
            'error'   => 'ui-label-red',
            'warning' => 'ui-label-yellow',
            'info'    => 'ui-label-blue',
            'default' => 'ui-label-gray',
            'primary' => 'ui-label-blue',
        ];
        $cls = $map[$this->badgeColor] ?? $this->badgeColor;

        return '<span class="ui-label ui-label-fill ' . htmlspecialcharsbx($cls) . '">' . $text . '</span>';
    }

    protected function renderLink(string $text): string
    {
        $target = htmlspecialcharsbx($this->linkTarget ?? '_blank');

        return '<a href="' . $text . '" target="' . $target . '" class="adminkit-preview__link">' . $text . '</a>';
    }
}
