<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

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
    protected bool $asCheckboxes = false;

    /** Render as a vertical checkbox list instead of a multi-select. */
    public function asCheckboxes(bool $v = true): static
    {
        $this->asCheckboxes = $v;
        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $rawValue = $this->resolveValue($value);
        $selected = $this->parseIds($rawValue);
        $options = $this->loadOptions();
        $name = htmlspecialcharsbx($this->column);

        if ($this->asCheckboxes) {
            return $this->renderCheckboxes($name, $options, $selected);
        }

        return $this->renderMultiSelect($name, $options, $selected);
    }

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

    protected function renderMultiSelect(string $name, array $options, array $selected): string
    {
        $inputName = htmlspecialcharsbx($name) . '[]';
        $optionsHtml = '';
        foreach ($options as $optVal => $optLabel) {
            $sel = in_array((string)$optVal, $selected, true) ? ' selected' : '';
            $optionsHtml .= '<option value="' . htmlspecialcharsbx($optVal) . '"' . $sel . '>'
                . htmlspecialcharsbx($optLabel) . '</option>';
        }

        return <<<HTML
        <div class="ui-ctl ui-ctl-dropdown">
            <select class="ui-ctl-element" name="{$inputName}" multiple size="5">{$optionsHtml}</select>
        </div>
        HTML;
    }

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

    public function previewValue(mixed $value): string
    {
        $ids = $this->parseIds($value);
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

    protected function loadLabels(array $ids): array
    {
        if (!$this->dataManagerClass || !class_exists($this->dataManagerClass)) {
            return $ids;
        }
        try {
            $result = $this->dataManagerClass::getList([
                'select' => [$this->valueColumn, $this->titleColumn],
                'filter' => ['@' . $this->valueColumn => $ids],
            ]);
            $map = [];
            while ($row = $result->fetch()) {
                $map[(string)$row[$this->valueColumn]] = (string)($row[$this->titleColumn] ?? '');
            }
            return array_map(fn ($id) => $map[$id] ?? $id, $ids);
        } catch (Throwable) {
            return $ids;
        }
    }

    public function serializePostValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return implode(',', array_filter(array_map('strval', $value)));
        }
        return $value;
    }
}
