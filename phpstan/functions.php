<?php

declare(strict_types=1);

if (!function_exists('htmlspecialcharsbx')) {
    function htmlspecialcharsbx(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('bitrix_sessid_post')) {
    function bitrix_sessid_post(): string
    {
        return '<input type="hidden" name="sessid" value="sessid">';
    }
}

if (!function_exists('check_bitrix_sessid')) {
    function check_bitrix_sessid(): bool
    {
        return true;
    }
}

if (!function_exists('LocalRedirect')) {
    function LocalRedirect(string $url): void
    {
    }
}

if (!class_exists('CUtil')) {
    class CUtil
    {
        public static function JSEscape(string $str): string
        {
            return $str;
        }
    }
}

if (!class_exists('CFile')) {
    class CFileResultMock
    {
        /** @return array<string, mixed>|false */
        public function Fetch(): array|bool
        {
            return [];
        }
    }

    class CFile
    {
        /** @return array<string, mixed>|false */
        public static function MakeFileArray(mixed $file, mixed $mimeType = '', mixed $forceFile = false): array|bool
        {
            return [];
        }

        public static function Delete(mixed $id): void
        {
        }

        public static function SaveFile(mixed $file, string $dir): mixed
        {
            return 1;
        }

        /** @return array<string, mixed>|false */
        public static function GetFileArray(mixed $id): array|bool
        {
            return [];
        }

        public static function GetByID(mixed $id): CFileResultMock
        {
            return new CFileResultMock();
        }

        /** @return array<string, mixed>|false */
        public static function ResizeImageGet(mixed $file, array $sizes, int $type = 1, bool $bInitSizes = false, mixed $arFilters = false, mixed $bImmediate = false, mixed $jpgQuality = false): array|bool
        {
            return [];
        }
    }
}
