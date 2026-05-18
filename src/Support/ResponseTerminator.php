<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

final class ResponseTerminator
{
    public static function clearOutputBuffers(): void
    {
        if (self::isTesting()) {
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    public static function terminate(): void
    {
        if (self::isTesting()) {
            return;
        }

        exit;
    }

    public static function isTesting(): bool
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            return true;
        }

        return (string)getenv('MB_ADMIN_KIT_TESTING') === '1';
    }
}
