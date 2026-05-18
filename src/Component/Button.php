<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class Button
{
    public static function save(string $text = '', array $attrs = []): string
    {
        $text = $text !== '' ? $text : LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_BUTTON_SAVE', 'Save');

        return static::render(
            'submit',
            $text,
            'ui-btn ui-btn-success',
            array_merge(['name' => 'save', 'value' => 'Y'], $attrs)
        );
    }

    public static function cancel(string $text = '', string $onclick = 'window.history.back()'): string
    {
        $text = $text !== '' ? $text : LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_BUTTON_CANCEL', 'Cancel');

        return static::render('button', $text, 'ui-btn ui-btn-link', ['onclick' => $onclick]);
    }

    public static function add(string $url, string $text = ''): string
    {
        $text = $text !== '' ? $text : LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_BUTTON_ADD', 'Add');
        $escapedUrl = htmlspecialcharsbx($url);

        return '<a href="' . $escapedUrl . '" class="ui-btn ui-btn-primary ui-btn-icon-add">' . htmlspecialcharsbx(
            $text
        ) . '</a>';
    }

    public static function primary(string $text, array $attrs = []): string
    {
        return static::render('button', $text, 'ui-btn ui-btn-primary', $attrs);
    }

    public static function secondary(string $text, array $attrs = []): string
    {
        return static::render('button', $text, 'ui-btn ui-btn-light-border', $attrs);
    }

    public static function danger(string $text, array $attrs = []): string
    {
        return static::render('button', $text, 'ui-btn ui-btn-danger', $attrs);
    }

    public static function link(string $text, array $attrs = []): string
    {
        return static::render('button', $text, 'ui-btn ui-btn-link', $attrs);
    }

    public static function icon(string $icon, string $title = '', array $attrs = []): string
    {
        return static::render(
            'button',
            '',
            'ui-btn ui-btn-icon ui-btn-icon-' . $icon,
            array_merge(['title' => $title], $attrs)
        );
    }

    public static function panel(array $buttons): string
    {
        $html = '<div class="ui-button-panel">';
        foreach ($buttons as $button) {
            $html .= $button;
        }
        $html .= '</div>';

        return $html;
    }

    protected static function render(string $type, string $text, string $class, array $attrs = []): string
    {
        $attrStr = '';
        foreach ($attrs as $key => $value) {
            $attrStr .= ' ' . htmlspecialcharsbx($key) . '="' . htmlspecialcharsbx((string)$value) . '"';
        }

        return '<button type="' . $type . '" class="' . $class . '"' . $attrStr . '>' . htmlspecialcharsbx(
            $text
        ) . '</button>';
    }

}
