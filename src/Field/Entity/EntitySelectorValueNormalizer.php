<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

use MB\Bitrix\AdminKit\Support\AdminCollection;

final class EntitySelectorValueNormalizer
{
    /** @return list<string> */
    public function parseIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', AdminCollection::make($value)->all()), static fn (string $id): bool => $id !== ''));
        }

        $str = (string)$value;
        if (str_contains($str, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $str)), static fn (string $id): bool => $id !== ''));
        }

        return [$str];
    }

    public function normalizeValue(mixed $value, bool $multiple): mixed
    {
        if ($multiple) {
            if ($value === null || $value === '') {
                return [];
            }

            return array_values(array_filter(AdminCollection::make(is_array($value) ? $value : [$value])->all(), static fn ($id): bool => $id !== null && $id !== ''));
        }

        if (is_array($value)) {
            $first = reset($value);
            return $first === false || $first === '' ? null : $first;
        }

        return $value === '' ? null : $value;
    }
}
