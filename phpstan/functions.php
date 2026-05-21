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
