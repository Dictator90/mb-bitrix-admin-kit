<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use Bitrix\Main\Localization\Loc;

final class LocalizedMessage
{
    /**
     * @param array<string,mixed> $replace
     */
    public static function get(string $messageFile, string $key, string $fallback, array $replace = []): string
    {
        if (!class_exists(Loc::class)) {
            return $fallback;
        }

        Loc::loadMessages($messageFile);
        $message = Loc::getMessage($key, $replace);

        if ($message === null || $message === '') {
            return $replace === [] ? $fallback : str_replace(array_keys($replace), array_values($replace), $fallback);
        }

        return (string) $message;
    }
}
