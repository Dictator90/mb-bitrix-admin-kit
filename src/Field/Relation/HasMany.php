<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use MB\Bitrix\AdminKit\Relation\RelationType;

final class HasMany extends RelationField
{
    protected string $renderMode = 'table';

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

    public function asTable(): static
    {
        $this->renderMode = 'table';

        return $this;
    }

    public function asEmbeddedForm(): static
    {
        $this->renderMode = 'embedded';

        return $this;
    }

    public function asGrid(): static
    {
        $this->renderMode = 'grid';

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);

        return match ($this->renderMode) {
            'embedded', 'grid' => $this->renderEmbeddedPreview($values),
            default => $this->renderTablePreview($values),
        };
    }

    /** @param array<int|string,mixed> $values */
    protected function renderTablePreview(array $values): string
    {
        if ($values === []) {
            return '<span class="adminkit-relation-preview">—</span>';
        }

        $html = '<table class="adminkit-relation-table"><tbody>';
        foreach ($values as $row) {
            $html .= '<tr><td>' . htmlspecialchars(is_array($row) ? json_encode($row, JSON_UNESCAPED_UNICODE) : (string) $row) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /** @param array<int|string,mixed> $values */
    protected function renderEmbeddedPreview(array $values): string
    {
        return '<div class="adminkit-relation-embedded-preview adminkit-relation-embedded-preview--limited">'
            . htmlspecialcharsbx('Embedded HasMany editing is limited; use object graph form mode for supported saves.')
            . '</div>';
    }
}
