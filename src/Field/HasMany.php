<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Relation\RelationType;

final class HasMany extends RelationField
{
    public function isToMany(): bool
    {
        return true;
    }

    public function relationDefault(): mixed
    {
        return [];
    }

    public function relationType(): RelationType
    {
        return RelationType::HAS_MANY;
    }

    public function renderFormField(mixed $value = null): string
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);

        return '<span>' . htmlspecialchars(implode(', ', array_map('strval', $values))) . '</span>';
    }
}
