<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Support\Collection;

final class AdminCollection
{
    /** @param iterable<mixed>|Collection $items */
    public static function make(Collection|iterable $items = []): Collection
    {
        if ($items instanceof Collection) {
            return $items;
        }

        if ($items instanceof \Traversable) {
            $items = iterator_to_array($items);
        }

        return new Collection($items);
    }

    /** @param iterable<mixed>|Collection $items */
    public function __invoke(Collection|iterable $items = []): Collection
    {
        return self::make($items);
    }
}
