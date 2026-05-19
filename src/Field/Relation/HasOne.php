<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use MB\Bitrix\AdminKit\Relation\RelationType;

final class HasOne extends RelationField
{
    protected string $renderMode = 'preview';

    public function isToMany(): bool
    {
        return false;
    }

    public function default(mixed $value): static
    {
        $this->relationDefault = $value;

        return parent::default($value);
    }

    public function relationType(): RelationType
    {
        return RelationType::HAS_ONE;
    }

    public function asPreview(): static
    {
        $this->renderMode = 'preview';

        return $this;
    }

    public function asEmbeddedForm(): static
    {
        $this->renderMode = 'embedded';

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        if ($this->renderMode === 'embedded' && is_array($value)) {
            return $this->renderEmbeddedPreview($value);
        }

        return '<span class="adminkit-relation-preview">' . htmlspecialchars((string)($this->resolveValue($value) ?? '')) . '</span>';
    }

    /** @param array<string,mixed> $value */
    protected function renderEmbeddedPreview(array $value): string
    {
        $items = [];
        foreach ($value as $key => $itemValue) {
            if (is_scalar($itemValue)) {
                $items[] = htmlspecialcharsbx((string) $key) . ': ' . htmlspecialcharsbx((string) $itemValue);
            }
        }

        return '<div class="adminkit-relation-embedded-preview">' . implode('<br>', $items) . '</div>';
    }
}
