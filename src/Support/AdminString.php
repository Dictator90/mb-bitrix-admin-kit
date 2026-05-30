<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use Bitrix\Main\Application;

final class AdminString
{
    public static function slug(string $value, string $separator = '_'): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (class_exists(\CUtil::class) && method_exists(\CUtil::class, 'translit')) {
            return trim((string) \CUtil::translit($value, self::translitLanguage(), [
                'max_len' => max(mb_strlen($value) * 2, 255),
                'change_case' => 'L',
                'replace_space' => $separator,
                'replace_other' => $separator,
                'delete_repeat_replace' => true,
            ]), $separator);
        }

        $value = preg_replace('/[^\pL\pN]+/u', $separator, $value) ?: '';

        return trim(mb_strtolower($value), $separator);
    }

    private static function translitLanguage(): string
    {
        if (defined('LANGUAGE_ID')) {
            $languageId = constant('LANGUAGE_ID');
            if (is_string($languageId) && $languageId !== '') {
                return $languageId;
            }
        }

        if (class_exists(Application::class)) {
            try {
                $language = Application::getInstance()->getContext()->getLanguage();
                if ($language !== '') {
                    return $language;
                }
            } catch (\Throwable) {
            }
        }

        return 'ru';
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

    public static function gridId(string $value): string
    {
        return mb_strtoupper(self::id('adminkit_grid', $value));
    }
    public static function filterId(string $value): string
    {
        return self::id(self::gridId($value), 'filter');
    }
    public static function formId(string $value): string
    {
        return self::id('adminkit_form', $value);
    }
    public static function fieldHtmlId(string $formId, string $field): string
    {
        return self::id($formId, $field);
    }
    public static function actionId(string $value): string
    {
        return self::id('adminkit_action', $value);
    }
    public static function cacheKey(string $prefix, array $parts): string
    {
        return self::id($prefix, hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''));
    }

    public static function safeKey(string $value): string
    {
        return mb_strtoupper(str_replace('-', '_', self::slug($value)));
    }

    public static function htmlId(string $prefix, string $value): string
    {
        return self::id($prefix, $value);
    }
}
