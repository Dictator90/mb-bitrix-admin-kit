<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use MB\Bitrix\AdminKit\Relation\MediatorPivotKeyResolver;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationType;
use Throwable;

/**
 * Select multiple records from a DataManager table.
 * Values are stored as comma-separated IDs (or whatever valueColumn holds).
 *
 * Usage:
 *   BelongsToMany::make('Теги', 'TAG_IDS', TagTable::class)
 *       ->titleColumn('NAME')
 *       ->filter(['ACTIVE' => 'Y'])
 *       ->orderBy('NAME')
 *       ->asCheckboxes()   // optional: render as checkbox list
 */
class BelongsToMany extends BelongsTo
{
    protected bool $storedAsCsv = true;
    protected string $saveStrategy = 'orm';
    protected bool $ormSaveExplicit = false;

    protected bool $asCheckboxes = false;

    /** Per-instance id→label memo, reused across grid rows to avoid a query per row. */
    /** @var array<string,string> */
    private array $labelMapCache = [];

    /** Render as a vertical checkbox list instead of a multi-select. */
    public function asCheckboxes(bool $v = true): static
    {
        $this->asCheckboxes = $v;
        return $this;
    }

    public function storedAsCsv(bool $enabled = true): static
    {
        $this->storedAsCsv = $enabled;

        return $this;
    }

    public function saveUsingOrm(): static
    {
        $this->saveStrategy = 'orm';
        $this->ormSaveExplicit = true;

        return $this;
    }

    public function saveUsingManualSync(): static
    {
        $this->saveStrategy = 'manual';

        return $this;
    }

    public function saveStrategy(): string
    {
        return $this->saveStrategy;
    }

    /**
     * Persist via pivot table instead of EntityObject::set() on a ManyToMany relation.
     *
     * Required for runtime-registered ManyToMany fields: Bitrix can load them in queries,
     * but EO_* system setter does not accept ManyToMany values assigned through addField().
     */
    public function persistsViaPivotTable(?RelationMetadata $metadata = null): bool
    {
        if ($this->saveStrategy === 'manual') {
            return true;
        }

        if (!$this->hasExplicitRelationDefinition()) {
            return false;
        }

        if ($metadata !== null) {
            return $this->canPersistViaPivotTable(
                $metadata->mediatorEntity,
                $metadata->foreignPivotKey,
                $metadata->relatedPivotKey,
                $metadata->localMediatorReference,
                $metadata->remoteMediatorReference,
            );
        }

        return $this->canPersistViaPivotTable(
            $this->pivotTableClass,
            $this->foreignPivotKeyName,
            $this->relatedPivotKeyName,
            (string) ($this->localMediatorReferenceName ?? ''),
            (string) ($this->remoteMediatorReferenceName ?? ''),
        );
    }

    private function canPersistViaPivotTable(
        ?string $mediatorEntity,
        ?string $foreignPivotKey,
        ?string $relatedPivotKey,
        string $localMediatorReference,
        string $remoteMediatorReference,
    ): bool {
        if ($mediatorEntity === null || $mediatorEntity === '') {
            return false;
        }

        if (
            $foreignPivotKey !== null
            && $foreignPivotKey !== ''
            && $relatedPivotKey !== null
            && $relatedPivotKey !== ''
        ) {
            return true;
        }

        if ($localMediatorReference === '' || $remoteMediatorReference === '') {
            return false;
        }

        return MediatorPivotKeyResolver::resolve($mediatorEntity, $localMediatorReference, $remoteMediatorReference) !== null;
    }

    public function isOrmRelationMode(): bool
    {
        if (!$this->storedAsCsv) {
            return true;
        }

        if ($this->ormSaveExplicit) {
            return true;
        }

        if ($this->saveStrategy === 'manual') {
            return true;
        }

        if ($this->relationName() !== null && $this->relationName() !== '') {
            return true;
        }

        if ($this->relatedTableClass !== null && $this->relatedTableClass !== '') {
            return true;
        }

        if ($this->pivotTableClass !== null && $this->pivotTableClass !== '') {
            return true;
        }

        return false;
    }

    public function isStoredAsCsv(): bool
    {
        return !$this->isOrmRelationMode();
    }

    public function relationType(): RelationType
    {
        return RelationType::BELONGS_TO_MANY;
    }

    public function renderFormField(mixed $value = null): string
    {
        if ($this->renderMode === 'dialog_selector') {
            return $this->renderDialogSelectorField($value);
        }

        $rawValue = $this->resolveFormValue($value);
        $selected = $this->parseIds($rawValue);
        $options = $this->loadOptions();
        $name = htmlspecialcharsbx($this->column);

        if ($this->asCheckboxes) {
            return $this->renderCheckboxes($name, $options, $selected);
        }

        return $this->renderMultiSelect($name, $options, $selected);
    }

    /**
     * Выбранные id для Dialog Selector (multiple).
     *
     * @return list<string>
     */
    protected function dialogSelectedIds(mixed $value): array
    {
        return $this->parseIds($this->resolveFormValue($value));
    }

    protected function dialogMultiple(): bool
    {
        return true;
    }

    /** @return list<string> */
    protected function parseIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('strval', explode(',', $value))));
        }
        return [];
    }

    /**
     * @param array<string, string> $options
     * @param list<string> $selected
     */
    protected function renderMultiSelect(string $name, array $options, array $selected): string
    {
        $inputName = htmlspecialcharsbx($name) . '[]';
        $optionsHtml = '';
        foreach ($options as $optVal => $optLabel) {
            $sel = in_array((string)$optVal, $selected, true) ? ' selected' : '';
            $optionsHtml .= '<option value="' . htmlspecialcharsbx($optVal) . '"' . $sel . '>'
                . htmlspecialcharsbx($optLabel) . '</option>';
        }

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-dropdown');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <select{$elementAttrs} name="{$inputName}" multiple size="5">{$optionsHtml}</select>
        </div>
        HTML;
    }

    /**
     * @param array<string, string> $options
     * @param list<string> $selected
     */
    protected function renderCheckboxes(string $name, array $options, array $selected): string
    {
        $html = '<div class="adminkit-checkbox-list">';
        foreach ($options as $optVal => $optLabel) {
            $id = 'cb_' . htmlspecialcharsbx($name) . '_' . htmlspecialcharsbx((string)$optVal);
            $checked = in_array((string)$optVal, $selected, true) ? ' checked' : '';
            $html .= '<label class="ui-ctl ui-ctl-checkbox adminkit-checkbox-list__item">'
                . '<input type="checkbox" class="ui-ctl-element" id="' . $id . '" '
                . 'name="' . htmlspecialcharsbx($name) . '[]" '
                . 'value="' . htmlspecialcharsbx($optVal) . '"' . $checked . '>'
                . '<div class="ui-ctl-label-text">' . htmlspecialcharsbx($optLabel) . '</div>'
                . '</label>';
        }
        $html .= '</div>';
        return $html;
    }

    protected function resolveFormValue(mixed $value): mixed
    {
        if (is_array($value) && array_is_list($value)) {
            return $value;
        }

        return $this->resolveValue($value);
    }

    public function previewValue(mixed $value): string
    {
        $ids = $this->parseIds($this->resolveFormValue($value));
        if (empty($ids)) {
            return '—';
        }

        if ($this->optionsCallback !== null) {
            $options = ($this->optionsCallback)();
            $labels = array_map(fn ($id) => $options[$id] ?? $id, $ids);
        } else {
            $labels = $this->loadLabels($ids);
        }

        return htmlspecialcharsbx(implode(', ', $labels));
    }

    /**
     * @param list<string> $ids
     * @return list<string>
     */
    protected function loadLabels(array $ids): array
    {
        if (!$this->dataManagerClass || !class_exists($this->dataManagerClass)) {
            return $ids;
        }

        $missing = array_values(array_filter(
            $ids,
            fn (string $id): bool => !array_key_exists($id, $this->labelMapCache),
        ));

        if ($missing !== []) {
            try {
                $result = $this->dataManagerClass::getList([
                    'select' => [$this->valueColumn, $this->titleColumn],
                    'filter' => ['@' . $this->valueColumn => $missing],
                ]);
                while ($row = $result->fetch()) {
                    $this->labelMapCache[(string)$row[$this->valueColumn]] = (string)($row[$this->titleColumn] ?? '');
                }
            } catch (Throwable) {
                return $ids;
            }

            foreach ($missing as $id) {
                $this->labelMapCache[$id] ??= $id;
            }
        }

        return array_map(fn (string $id): string => $this->labelMapCache[$id] ?? $id, $ids);
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->isOrmRelationMode()) {
            return $this->normalizeOrmIdList($value);
        }

        if (is_array($value)) {
            return implode(',', array_values(array_filter(array_map('strval', $value), static fn (string $id): bool => $id !== '')));
        }

        return parent::normalize($value);
    }

    public function serializePostValue(mixed $value): mixed
    {
        if ($this->isOrmRelationMode() && is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), static fn (string $id): bool => $id !== ''));
        }

        if (is_array($value)) {
            return implode(',', array_filter(array_map('strval', $value)));
        }

        return $value;
    }

    /**
     * @return list<int|string>
     */
    private function normalizeOrmIdList(mixed $value): array
    {
        if ($value === null || $value === '' || $value === false) {
            return [];
        }

        if (is_array($value)) {
            $ids = [];
            foreach ($value as $id) {
                if ($id === null || $id === '' || $id === false) {
                    continue;
                }

                $ids[] = is_numeric($id) ? (int) $id : (string) $id;
            }

            return array_values($ids);
        }

        return [is_numeric($value) ? (int) $value : (string) $value];
    }
}
