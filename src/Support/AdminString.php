<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Support\Str;

final class AdminString
{
    public static function slug(string $value, string $separator = '_'): string
    {
        return Str::slug($value, $separator);
    }

    public static function id(string $prefix, string $value): string
    {
        $base = str_replace('-', '_', self::slug($value));
        return trim($prefix . '_' . $base, '_');
    }

    public static function resourceId(string $class): string
    {
        $parts = explode('\\', $class);
        $short = preg_replace('/Resource$/', '', end($parts) ?: $class) ?: $class;
        return self::slug($short);
    }

    public static function gridId(string $value): string { return mb_strtoupper(self::id('adminkit_grid', $value)); }
    public static function filterId(string $value): string { return self::id(self::gridId($value), 'filter'); }
    public static function formId(string $value): string { return self::id('adminkit_form', $value); }
    public static function fieldHtmlId(string $formId, string $field): string { return self::id($formId, $field); }
    public static function actionId(string $value): string { return self::id('adminkit_action', $value); }
}
