<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use Bitrix\Main\UI\Extension;

class Notification
{
    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR = 'danger';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';

    public static function success(string $message, int $autoclose = 3000): string
    {
        return static::show($message, static::TYPE_SUCCESS, $autoclose);
    }

    public static function error(string $message, int $autoclose = 0): string
    {
        return static::show($message, static::TYPE_ERROR, $autoclose);
    }

    public static function warning(string $message, int $autoclose = 5000): string
    {
        return static::show($message, static::TYPE_WARNING, $autoclose);
    }

    public static function info(string $message, int $autoclose = 3000): string
    {
        return static::show($message, static::TYPE_INFO, $autoclose);
    }

    /**
     * Returns inline JS string for use in onclick or script blocks.
     */
    public static function show(string $message, string $type = self::TYPE_INFO, int $autoclose = 3000): string
    {
        $jsMessage = \CUtil::JSEscape($message);
        $autocloseProp = $autoclose > 0 ? "autoClose: {$autoclose}," : '';

        return <<<JS
        BX.UI.Notification.Center.notify({
            content: BX.message ? BX.message('{$jsMessage}') || '{$jsMessage}' : '{$jsMessage}',
            type: '{$type}',
            {$autocloseProp}
        });
        JS;
    }

    /**
     * Renders an HTML alert block (static, no JS required).
     */
    public static function alert(string $message, string $type = self::TYPE_INFO): string
    {
        $escapedMessage = htmlspecialcharsbx($message);
        $cssType = match ($type) {
            static::TYPE_SUCCESS => 'ui-alert-success',
            static::TYPE_ERROR => 'ui-alert-danger',
            static::TYPE_WARNING => 'ui-alert-warning',
            default => 'ui-alert-info',
        };

        return '<div class="ui-alert ' . $cssType . '"><span class="ui-alert-message">' . $escapedMessage . '</span></div>';
    }

    /**
     * Loads the ui.notification extension and renders a script that shows a notification on page load.
     */
    public static function renderOnLoad(string $message, string $type = self::TYPE_SUCCESS, int $autoclose = 3000): string
    {
        Extension::load(['ui.notification']);
        $js = static::show($message, $type, $autoclose);

        return <<<HTML
        <script>BX.ready(function(){ {$js} });</script>
        HTML;
    }
}
