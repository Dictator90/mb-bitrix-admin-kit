<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

final class HasOne extends RelationField
{
    public function isToMany(): bool
    {
        return false;
    }

    public function default(mixed $value): static
    {
        $this->relationDefault = $value;

        return parent::default($value);
    }

    public function renderFormField(mixed $value = null): string
    {
        return '<span>' . htmlspecialchars((string)($value ?? '')) . '</span>';
    }
}
