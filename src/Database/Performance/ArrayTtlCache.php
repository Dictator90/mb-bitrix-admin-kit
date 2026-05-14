<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Performance;

final class ArrayTtlCache
{
    /** @var array<string,array{expires:int,value:mixed}> */
    private static array $items = [];

    public static function get(string $key): mixed
    {
        $item = self::$items[$key] ?? null;
        if ($item === null) {
            return null;
        }

        if ($item['expires'] < time()) {
            unset(self::$items[$key]);
            return null;
        }

        return $item['value'];
    }

    public static function set(string $key, mixed $value, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        self::$items[$key] = ['expires' => time() + $ttl, 'value' => $value];
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function clear(): void
    {
        self::$items = [];
    }
}
