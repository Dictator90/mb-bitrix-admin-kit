<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\Extension;

/**
 * Добавляет скалярному текстовому полю повторяемый (множественный) режим.
 *
 *  - multiple(false): один `<input>`, отрисованный {@see renderScalarControl()}.
 *    Значение опции — строка.
 *
 *  - multiple(true): список инпутов с кнопками добавления/удаления. Значение
 *    опции — плоский JSON-массив строк (["…","…"]); POST-имена позиционные
 *    ("{column}[{index}]") и перенумеровываются на клиенте при add/remove.
 *
 * Поле-хозяин обязано определить {@see scalarInputType()} ('tel'|'email'|'text'…)
 * и может переопределить {@see scalarInputExtraAttrs()} и
 * {@see defaultAddButtonLabel()}.
 */
trait RepeatableScalar
{
    protected ?int $maxItems = null;

    protected ?string $repeatableAddLabel = null;

    abstract protected function scalarInputType(): string;

    protected function scalarInputExtraAttrs(): string
    {
        return '';
    }

    protected function defaultAddButtonLabel(): string
    {
        return 'Добавить';
    }

    public function maxItems(?int $max): static
    {
        $this->maxItems = $max !== null ? max(1, $max) : null;

        return $this;
    }

    public function addButtonLabel(string $label): static
    {
        $this->repeatableAddLabel = $label;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        if ($this->multiple) {
            return $this->renderRepeatable($value, $formData);
        }

        $scalar = is_array($value) ? '' : (string)($this->resolveOwnValue($value) ?? '');

        return $this->renderScalarControl(htmlspecialcharsbx($this->column), $scalar, $formData, true)
            . $this->fieldAssets();
    }

    /**
     * Extra HTML (scripts/styles) appended once after the control in both single
     * and multiple modes. Host fields override this to inject their own behaviour
     * (e.g. input masking). Default: nothing.
     */
    protected function fieldAssets(): string
    {
        return '';
    }

    /** @param array<string,mixed> $formData */
    protected function renderRepeatable(mixed $value, array $formData): string
    {
        $items = $this->parseList($this->resolveOwnValue($value));
        $groupId = 'adminkit_repeat_' . $this->column . '_' . Random::getString(6);
        $readonly = $this->isReadOnlyFor($formData);

        $rowsHtml = '';
        foreach (array_values($items) as $index => $item) {
            $rowsHtml .= $this->renderRepeatableRow($groupId, $index, (string)$item, $readonly, $formData);
        }

        $templateHtml = $this->renderRepeatableRow($groupId, '__INDEX__', '', $readonly, $formData);
        $templateJson = htmlspecialcharsbx(json_encode($templateHtml, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-repeat');
        $addLabel = htmlspecialcharsbx($this->repeatableAddLabel ?? $this->defaultAddButtonLabel());
        $maxAttr = $this->maxItems !== null ? ' data-max-items="' . $this->maxItems . '"' : '';
        $sortableAttr = ($this->sortable && !$readonly) ? ' data-repeat-sortable="1"' : '';

        Extension::load(['ui.buttons']);
        $addButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-light-border ui-btn-sm ui-btn-icon-add adminkit-repeat-add" data-group="{$groupId}">{$addLabel}</button>
            HTML;

        return <<<HTML
        <div{$wrapperAttrs} id="{$groupId}" data-repeat-group="{$groupId}" data-repeat-template="{$templateJson}"{$maxAttr}{$sortableAttr}>
            <div class="adminkit-repeat-rows">{$rowsHtml}</div>
            {$addButton}
        </div>
        {$this->renderRepeatableScriptOnce()}
        {$this->renderRepeatableStyleOnce()}
        {$this->fieldAssets()}
        HTML;
    }

    /** @param array<string,mixed> $formData */
    protected function renderRepeatableRow(string $groupId, int|string $index, string $value, bool $readonly, array $formData): string
    {
        $name = htmlspecialcharsbx($this->column . '[' . $index . ']');
        $control = $this->renderScalarControl($name, $value, $formData, false);

        $sortable = $this->sortable && !$readonly;
        $rowClass = 'adminkit-repeat-row' . ($sortable ? ' adminkit-repeat-row--sortable' : '');
        $handle = $sortable
            ? '<span class="adminkit-repeat-handle" draggable="true" title="Перетащите для сортировки">⠿</span>'
            : '';

        $removeButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-icon-remove ui-btn-link ui-btn-sm adminkit-repeat-remove" title="Удалить" data-group="{$groupId}"></button>
            HTML;

        return <<<HTML
        <div class="{$rowClass}" data-repeat-row>
            {$handle}
            <div class="adminkit-repeat-cell">{$control}</div>
            <div class="adminkit-repeat-action">{$removeButton}</div>
        </div>
        HTML;
    }

    /** @param array<string,mixed> $formData */
    protected function renderScalarControl(string $name, string $rawValue, array $formData, bool $withRequired): string
    {
        $type = htmlspecialcharsbx($this->scalarInputType());
        $val = htmlspecialcharsbx($rawValue);
        $reqAttr = $withRequired ? $this->requiredAttr() : '';
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $placeholderAttr = $this->placeholderAttr();
        $reactiveAttrs = $withRequired ? $this->renderReactiveAttrs() : '';
        $extraAttrs = $this->scalarInputExtraAttrs();

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-textbox');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <input type="{$type}"{$elementAttrs} name="{$name}" value="{$val}"{$extraAttrs}{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>
        </div>
        HTML;
    }

    /**
     * Returns the field's own stored value. When fed a form-data map keyed by
     * columns, extract this field's column; otherwise the value already IS this
     * field's value — pass it through as-is.
     */
    protected function resolveOwnValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists($this->column, $value)) {
            return $value[$this->column];
        }

        return $value ?? $this->value ?? $this->default;
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            return $this->parseList($value);
        }

        if (is_array($value)) {
            $value = reset($value);
            if ($value === false) {
                return null;
            }
        }

        return $this->normalizeScalar($value === null ? '' : (string)$value);
    }

    /**
     * Canonicalises a single value before storage. Default: trim, empty → null.
     * Host fields override this (e.g. {@see Phone} strips a phone to `+digits`).
     */
    protected function normalizeScalar(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    public function serializeOptionValue(mixed $value): string
    {
        if ($this->multiple) {
            $data = is_array($value) ? array_values($value) : [];

            try {
                return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException) {
                return '[]';
            }
        }

        return (string)($value ?? '');
    }

    public function unserializeOptionValue(string $value): mixed
    {
        return $this->multiple ? $this->parseList($value) : $value;
    }

    public function previewValue(mixed $value): string
    {
        if ($this->multiple) {
            return htmlspecialcharsbx(implode(', ', $this->parseList($value)));
        }

        return htmlspecialcharsbx((string)($value ?? ''));
    }

    /**
     * @return array<int, string>
     */
    protected function parseList(mixed $value): array
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    continue;
                }

                $normalized = $this->normalizeScalar((string)$item);
                if ($normalized !== null) {
                    $out[] = $normalized;
                }
            }

            return array_values($out);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }

            if ($trimmed[0] === '[') {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        return $this->parseList($decoded);
                    }
                } catch (\JsonException) {
                    // fall through to single-value handling
                }
            }

            $normalized = $this->normalizeScalar($trimmed);

            return $normalized === null ? [] : [$normalized];
        }

        return [];
    }

    protected function renderRepeatableScriptOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <script>
        (function () {
            if (window.__adminKitRepeatInit) { return; }
            window.__adminKitRepeatInit = true;

            function renumber(group) {
                var rows = group.querySelectorAll(':scope > .adminkit-repeat-rows > [data-repeat-row]');
                rows.forEach(function (row, index) {
                    row.querySelectorAll('[name]').forEach(function (el) {
                        el.name = el.name.replace(/\[(\d+|__INDEX__)\]/, '[' + index + ']');
                    });
                });
            }

            document.addEventListener('click', function (e) {
                var addBtn = e.target.closest('.adminkit-repeat-add');
                if (addBtn) {
                    var group = document.getElementById(addBtn.dataset.group);
                    if (!group) { return; }
                    var maxItems = parseInt(group.dataset.maxItems || '0', 10);
                    var rowsWrap = group.querySelector(':scope > .adminkit-repeat-rows');
                    var currentCount = rowsWrap.querySelectorAll(':scope > [data-repeat-row]').length;
                    if (maxItems > 0 && currentCount >= maxItems) { return; }

                    var template = JSON.parse(group.dataset.repeatTemplate);
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = template.trim();
                    var newRow = wrapper.firstElementChild;
                    newRow.querySelectorAll('[name]').forEach(function (el) {
                        el.name = el.name.replace('__INDEX__', String(currentCount));
                    });
                    rowsWrap.appendChild(newRow);
                    renumber(group);
                    return;
                }

                var removeBtn = e.target.closest('.adminkit-repeat-remove');
                if (removeBtn) {
                    var row = removeBtn.closest('[data-repeat-row]');
                    var groupEl = document.getElementById(removeBtn.dataset.group);
                    if (row && groupEl) {
                        row.remove();
                        renumber(groupEl);
                    }
                }
            });

            var dragRow = null;

            document.addEventListener('dragstart', function (e) {
                var handle = e.target.closest('.adminkit-repeat-handle');
                if (!handle) { return; }
                dragRow = handle.closest('[data-repeat-row]');
                if (!dragRow) { return; }
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* IE */ }
                dragRow.classList.add('adminkit-repeat-row--dragging');
            });

            document.addEventListener('dragover', function (e) {
                if (!dragRow) { return; }
                var group = dragRow.closest('[data-repeat-group][data-repeat-sortable]');
                if (!group) { return; }
                var over = e.target.closest('[data-repeat-row]');
                if (!over || over === dragRow || !group.contains(over)) { return; }
                e.preventDefault();
                var rect = over.getBoundingClientRect();
                if ((e.clientY - rect.top) > rect.height / 2) {
                    over.after(dragRow);
                } else {
                    over.before(dragRow);
                }
            });

            document.addEventListener('drop', function (e) {
                if (dragRow) { e.preventDefault(); }
            });

            document.addEventListener('dragend', function () {
                if (!dragRow) { return; }
                var group = dragRow.closest('[data-repeat-group]');
                dragRow.classList.remove('adminkit-repeat-row--dragging');
                dragRow = null;
                if (group) { renumber(group); }
            });
        })();
        </script>
        HTML;
    }

    protected function renderRepeatableStyleOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <style>
        .adminkit-repeat-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 8px;
        }
        .adminkit-repeat-rows:empty {
            margin-bottom: 0;
        }
        .adminkit-repeat-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }
        .adminkit-repeat-row--sortable {
            grid-template-columns: auto 1fr auto;
        }
        .adminkit-repeat-row--dragging {
            opacity: .5;
        }
        .adminkit-repeat-handle {
            cursor: grab;
            user-select: none;
            color: #a8adb4;
            line-height: 1;
            padding: 0 2px;
            align-self: center;
        }
        .adminkit-repeat-handle:active {
            cursor: grabbing;
        }
        .adminkit-repeat-cell .ui-ctl {
            width: 100%;
        }
        .adminkit-repeat-action {
            min-width: 24px;
        }
        </style>
        HTML;
    }
}
