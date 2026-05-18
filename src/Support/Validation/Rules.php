<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Validation;

use Closure;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class Rules
{
    public static function minLength(int $min, string $message = ''): Closure
    {
        return function (mixed $value) use ($min, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (mb_strlen((string)$value) < $min) {
                return $message ?: str_replace(
                    '#MIN#',
                    (string)$min,
                    LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_MIN_LENGTH', 'Minimum length: #MIN# characters.'),
                );
            }

            return true;
        };
    }

    public static function maxLength(int $max, string $message = ''): Closure
    {
        return function (mixed $value) use ($max, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (mb_strlen((string)$value) > $max) {
                return $message ?: str_replace(
                    '#MAX#',
                    (string)$max,
                    LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_MAX_LENGTH', 'Maximum length: #MAX# characters.'),
                );
            }

            return true;
        };
    }

    public static function email(string $message = ''): Closure
    {
        return function (mixed $value) use ($message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_EMAIL', 'Enter a valid email address.');
            }

            return true;
        };
    }

    public static function url(string $message = ''): Closure
    {
        return function (mixed $value) use ($message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!filter_var((string)$value, FILTER_VALIDATE_URL)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_URL', 'Enter a valid URL.');
            }

            return true;
        };
    }

    public static function numeric(string $message = ''): Closure
    {
        return function (mixed $value) use ($message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!is_numeric($value)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_NUMERIC', 'Field must contain a number.');
            }

            return true;
        };
    }

    public static function integer(string $message = ''): Closure
    {
        return function (mixed $value) use ($message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_INTEGER', 'Field must contain an integer.');
            }

            return true;
        };
    }

    public static function min(float|int $min, string $message = ''): Closure
    {
        return function (mixed $value) use ($min, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!is_numeric($value) || (float)$value < $min) {
                return $message ?: str_replace(
                    '#MIN#',
                    (string)$min,
                    LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_MIN', 'Minimum value: #MIN#.'),
                );
            }

            return true;
        };
    }

    public static function max(float|int $max, string $message = ''): Closure
    {
        return function (mixed $value) use ($max, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!is_numeric($value) || (float)$value > $max) {
                return $message ?: str_replace(
                    '#MAX#',
                    (string)$max,
                    LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_MAX', 'Maximum value: #MAX#.'),
                );
            }

            return true;
        };
    }

    public static function pattern(string $regex, string $message = ''): Closure
    {
        return function (mixed $value) use ($regex, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!preg_match($regex, (string)$value)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_PATTERN', 'Value does not match the required format.');
            }

            return true;
        };
    }

    public static function in(array $allowed, string $message = ''): Closure
    {
        return function (mixed $value) use ($allowed, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!in_array($value, $allowed, true)) {
                $allowedStr = implode(', ', $allowed);

                return $message ?: str_replace(
                    '#VALUES#',
                    $allowedStr,
                    LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_IN', 'Allowed values: #VALUES#.'),
                );
            }

            return true;
        };
    }

    public static function notIn(array $forbidden, string $message = ''): Closure
    {
        return function (mixed $value) use ($forbidden, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (in_array($value, $forbidden, true)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_NOT_IN', 'Invalid value.');
            }

            return true;
        };
    }

    public static function confirmed(string $confirmFieldName, mixed $confirmValue, string $message = ''): Closure
    {
        return function (mixed $value) use ($confirmFieldName, $confirmValue, $message): bool|string {
            if ($value !== $confirmValue) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_CONFIRMED', 'Field confirmation does not match.');
            }

            return true;
        };
    }

    public static function unique(callable $checker, string $message = ''): Closure
    {
        return function (mixed $value) use ($checker, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (!$checker($value)) {
                return $message ?: LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_RULE_UNIQUE', 'Value is already in use.');
            }

            return true;
        };
    }

}
