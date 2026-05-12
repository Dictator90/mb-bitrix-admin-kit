<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Validation;

use Closure;

class Rules
{
    public static function minLength(int $min, string $message = ''): Closure
    {
        return function (mixed $value) use ($min, $message): bool|string {
            if ($value === null || $value === '') {
                return true;
            }

            if (mb_strlen((string)$value) < $min) {
                return $message ?: "Минимальная длина: {$min} символов";
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
                return $message ?: "Максимальная длина: {$max} символов";
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
                return $message ?: 'Введите корректный email адрес';
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
                return $message ?: 'Введите корректный URL';
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
                return $message ?: 'Поле должно содержать число';
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
                return $message ?: 'Поле должно содержать целое число';
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
                return $message ?: "Минимальное значение: {$min}";
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
                return $message ?: "Максимальное значение: {$max}";
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
                return $message ?: 'Значение не соответствует формату';
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

                return $message ?: "Допустимые значения: {$allowedStr}";
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
                return $message ?: 'Недопустимое значение';
            }

            return true;
        };
    }

    public static function confirmed(string $confirmFieldName, mixed $confirmValue, string $message = ''): Closure
    {
        return function (mixed $value) use ($confirmFieldName, $confirmValue, $message): bool|string {
            if ($value !== $confirmValue) {
                return $message ?: "Поле не совпадает с полем подтверждения";
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
                return $message ?: 'Значение уже используется';
            }

            return true;
        };
    }
}
