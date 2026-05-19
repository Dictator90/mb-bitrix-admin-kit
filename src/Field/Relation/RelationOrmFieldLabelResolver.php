<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use Throwable;

/**
 * Resolves human-readable column titles from a Bitrix D7 ORM DataManager entity.
 */
final class RelationOrmFieldLabelResolver
{
    public static function resolve(?string $dataManagerClass, string $column): string
    {
        if ($dataManagerClass === null || $dataManagerClass === '' || !method_exists($dataManagerClass, 'getEntity')) {
            return $column;
        }

        try {
            $entity = $dataManagerClass::getEntity();
            if (!is_object($entity) || !method_exists($entity, 'hasField') || !$entity->hasField($column)) {
                return $column;
            }

            if (!method_exists($entity, 'getField')) {
                return $column;
            }

            $field = $entity->getField($column);
            if (is_object($field) && method_exists($field, 'getTitle')) {
                return self::normalizeTitle($field->getTitle(), $column);
            }
        } catch (Throwable) {
            return $column;
        }

        return $column;
    }

    public static function normalizeTitle(mixed $title, string $fallback): string
    {
        if (is_string($title)) {
            $normalized = trim($title);

            return $normalized !== '' ? $normalized : $fallback;
        }

        if (is_int($title) || is_float($title)) {
            return (string) $title;
        }

        if (is_object($title)) {
            if (method_exists($title, '__toString')) {
                $normalized = trim((string) $title);

                return $normalized !== '' ? $normalized : $fallback;
            }

            if (method_exists($title, 'getMessage')) {
                return self::normalizeTitle($title->getMessage(), $fallback);
            }
        }

        if (is_array($title)) {
            foreach (['MESSAGE', 'message', 'TEXT', 'text', 'TITLE', 'title'] as $key) {
                if (array_key_exists($key, $title)) {
                    return self::normalizeTitle($title[$key], $fallback);
                }
            }
        }

        return $fallback;
    }
}
