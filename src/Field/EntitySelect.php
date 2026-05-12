<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use Closure;

/**
 * Entity-selector field — wraps MB.UI.DialogSelector.DialogSelector.
 *
 * Uses the same JS wrapper as MB\Bitrix\UI\Control\Field\*SelectorField
 * so TagSelector, AJAX entity loading, and hidden inputs work automatically.
 *
 * Basic usage with a dynamic entity provider:
 *   EntitySelect::make('Менеджер', 'MANAGER_ID')
 *       ->entity('user-list')
 *       ->multiple()
 *
 * With entity-specific options:
 *   EntitySelect::make('Товар', 'PRODUCT_ID')
 *       ->entity('iblock-element-list', ['iblockId' => 5])
 *
 * Specialised subclasses (UserSelect, IblockSelect, etc.) pre-configure
 * entity so you don't need to call entity() manually.
 */
class EntitySelect extends Field
{
    protected array $entities = [];
    protected bool $multiple = false;

    /**
     * Closure to resolve human-readable titles for Grid cell preview.
     * Signature: fn(array $ids): array<id, string>
     */
    protected ?Closure $labelResolver = null;

    // ── Fluent API ───────────────────────────────────────────────────────

    /**
     * Add a dynamic entity to the selector dialog.
     *
     * @param string $id          Entity ID (e.g. 'user-list', 'iblock-element-list')
     * @param array  $entityOptions  Entity-specific options passed to the provider
     *                               (e.g. ['iblockId' => 5], ['filter' => [...]])
     *                               The current field value is injected as 'selected' automatically.
     */
    public function entity(string $id, array $entityOptions = []): static
    {
        $this->entities[] = [
            'id'            => $id,
            'options'       => $entityOptions,
            'dynamicLoad'   => true,
            'dynamicSearch' => true,
        ];

        return $this;
    }

    public function resetEntities(): static
    {
        $this->entities = [];

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * Closure to resolve human-readable titles for existing IDs (used in Grid preview).
     * Signature: fn(array $ids): array<id, string> — returns [id => title].
     */
    public function resolveLabels(Closure $resolver): static
    {
        $this->labelResolver = $resolver;

        return $this;
    }

    // ── Field contract ───────────────────────────────────────────────────

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function renderFormField(mixed $value = null): string
    {
        Extension::load(['ui', 'mb.ui.dialog-selector']);

        $name       = htmlspecialcharsbx($this->column);
        $uid        = 'tag_selector_' . $name . '_' . substr(md5(uniqid('', true)), 0, 8);
        $context    = 'ADMINKIT_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $name))
                      . '_' . substr(md5(uniqid('', true)), 0, 6);
        $multipleJs = $this->multiple ? 'true' : 'false';

        // Inject current selected IDs into each entity's options
        $ids      = $this->parseIds($this->resolveValue($value));
        $entities = $this->entities;
        foreach ($entities as &$ent) {
            $ent['options']['selected'] = $ids;
        }
        unset($ent);

        $entitiesJson = json_encode($entities, JSON_UNESCAPED_UNICODE);

        return <<<HTML
        <div id="{$uid}"></div>
        <script>
        BX.ready(function() {
            (new MB.UI.DialogSelector.DialogSelector({
                target: '#{$uid}',
                name: '{$name}',
                dialog: {
                    context: '{$context}',
                    dropdownMode: true,
                    preload: true,
                    entities: {$entitiesJson},
                },
                multiple: {$multipleJs},
            })).render();
        });
        </script>
        HTML;
    }

    // ── Grid preview ─────────────────────────────────────────────────────

    public function previewValue(mixed $value): string
    {
        $ids = $this->parseIds($value);
        if (empty($ids)) {
            return '';
        }

        $titles = $this->labelResolver ? ($this->labelResolver)($ids) : [];

        $parts = array_map(static function (string $id) use ($titles): string {
            $title = htmlspecialcharsbx((string)($titles[$id] ?? $id));
            return '<span class="adminkit-entity-select__chip">'
                . '<span class="adminkit-entity-select__chip-title">' . $title . '</span>'
                . '</span>';
        }, $ids);

        return implode(' ', $parts);
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function parseIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }
        $str = (string)$value;
        if (str_contains($str, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $str))));
        }

        return [$str];
    }
}
