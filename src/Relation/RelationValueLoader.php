<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class RelationValueLoader
{
    public function load(mixed $item, RelationField $field, RelationMetadata $metadata): mixed
    {
        $name = $metadata->relationName !== '' ? $metadata->relationName : $field->getColumn();
        $value = is_array($item) ? ($item[$name] ?? $item[$field->getColumn()] ?? null) : (method_exists($item, 'get') ? $item->get($name) : null);

        if ($field instanceof BelongsToMany && $field->serializePostValue([]) === []) {
            if (is_array($value)) {
                return array_values(array_map('strval', $value));
            }
        }

        return $value;
    }
}
