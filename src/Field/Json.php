<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;

/**
 * Structured JSON field modelled after MoonShine's Json — a set of sub-fields
 * (fields()) laid out horizontally as columns of one line, with a single
 * header row printing each sub-field label once.
 *
 *   Json::make('Преимущества', 'home_advantages')->fields([
 *       Text::make('Иконка', 'icon'),
 *       Text::make('Заголовок', 'title'),
 *       Textarea::make('Описание', 'description'),
 *   ])
 *
 * Layout is switchable with {@see layout()} / {@see stacked()}: the default
 * {@see LAYOUT_ROW} keeps sub-fields in a horizontal row under a shared header;
 * {@see LAYOUT_STACK} lays them vertically (one under another), each carrying
 * its own inline label. Stacking applies to both shapes below and, in the list
 * shape, turns every row into a self-contained card.
 *
 * Two shapes, switched by {@see multiple()}:
 *
 *  - multiple(true) (default): a user-managed LIST of rows. Value is a JSON
 *    array of associative arrays, one per row. Rows get add/remove buttons and,
 *    when {@see sortable()} is enabled, a drag handle for reordering. POST field
 *    names are positional ("{column}[{row}][{subColumn}]") and renumbered
 *    client-side on add/remove/reorder.
 *
 *  - multiple(false): a SINGLE object. Value is a JSON object ({}), one row of
 *    sub-fields with no add/remove/reorder controls. POST field names are
 *    "{column}[{subColumn}]".
 *
 * Rows stay `<div>`-based (not real `<table>/<tr>`) so the add-row template can
 * be cloned via `wrapper.innerHTML = template`. Layout is done with CSS grid.
 */
class Json extends Field
{
    /** @var FieldContract[] */
    protected array $schema = [];

    protected int $minRows = 0;

    protected ?int $maxRows = null;

    protected bool $sortable = false;

    /**
     * Sub-field layout inside a row: 'row' (default) lays them out horizontally
     * as columns under one shared header; 'stack' lays them vertically, one
     * under another, each with its own inline label (no shared header).
     */
    protected string $layout = self::LAYOUT_ROW;

    public const LAYOUT_ROW = 'row';

    public const LAYOUT_STACK = 'stack';

    protected string $addButtonLabel = 'Добавить';

    public function __construct(?string $label = null, ?string $column = null)
    {
        parent::__construct($label, $column);
        $this->multiple = true;
    }

    /** @param FieldContract[] $fields */
    public function fields(array $fields): static
    {
        $this->schema = $fields;

        return $this;
    }

    /** @param FieldContract[] $fields */
    public function schema(array $fields): static
    {
        return $this->fields($fields);
    }

    /** @return FieldContract[] */
    public function getSchema(): array
    {
        return $this->schema;
    }

    public function multiple(bool $value = true): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;

        return $this;
    }

    /** Alias of {@see sortable()}. */
    public function reorderable(bool $value = true): static
    {
        return $this->sortable($value);
    }

    /**
     * Sub-field layout: {@see LAYOUT_ROW} (horizontal columns, default) or
     * {@see LAYOUT_STACK} (vertical, one field under another).
     */
    public function layout(string $layout): static
    {
        $this->layout = $layout === self::LAYOUT_STACK ? self::LAYOUT_STACK : self::LAYOUT_ROW;

        return $this;
    }

    /** Lay sub-fields out vertically (one under another). Alias of layout(stack). */
    public function stacked(bool $value = true): static
    {
        return $this->layout($value ? self::LAYOUT_STACK : self::LAYOUT_ROW);
    }

    /** Alias of {@see stacked()}. */
    public function vertical(bool $value = true): static
    {
        return $this->stacked($value);
    }

    public function isStacked(): bool
    {
        return $this->layout === self::LAYOUT_STACK;
    }

    public function minRows(int $min): static
    {
        $this->minRows = max(0, $min);

        return $this;
    }

    public function maxRows(?int $max): static
    {
        $this->maxRows = $max !== null ? max(1, $max) : null;

        return $this;
    }

    public function addButtonLabel(string $label): static
    {
        $this->addButtonLabel = $label;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        return $this->multiple
            ? $this->renderMultiple($value, $formData)
            : $this->renderSingle($value, $formData);
    }

    /**
     * Returns the field's own stored value. When fed a form-data map keyed by
     * columns, extract this field's column; otherwise the value already IS this
     * field's value (a JSON list/object stored in options) — pass it through as-is.
     */
    protected function resolveOwnValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists($this->column, $value)) {
            return $value[$this->column];
        }

        return $value ?? $this->value ?? $this->default;
    }

    /** @param array<string,mixed> $formData */
    protected function renderMultiple(mixed $value, array $formData): string
    {
        $rows = $this->parseRows($this->resolveOwnValue($value));
        if ($rows === [] && $this->minRows > 0) {
            $rows = array_fill(0, $this->minRows, []);
        }

        $groupId = 'adminkit_json_' . $this->column . '_' . Random::getString(6);
        $readonly = $this->isReadOnlyFor($formData);

        $rowsHtml = '';
        foreach (array_values($rows) as $index => $row) {
            $rowsHtml .= $this->renderRow($groupId, $index, $row, $readonly);
        }

        $templateHtml = $this->renderRow($groupId, '__INDEX__', [], $readonly);
        $templateJson = htmlspecialcharsbx(json_encode($templateHtml, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-json');
        $label = htmlspecialcharsbx($this->label);
        $hint = $this->renderHint();
        $addLabel = htmlspecialcharsbx($this->addButtonLabel);
        $maxAttr = $this->maxRows !== null ? ' data-max-rows="' . $this->maxRows . '"' : '';
        $sortableAttr = $this->sortable ? ' data-json-sortable="1"' : '';
        $headerHtml = $this->isStacked()
            ? ''
            : '<div class="adminkit-json-header" style="' . $this->gridTemplateColumns() . '">' . $this->renderHeader(true) . '</div>';
        Extension::load(['ui.buttons']);
        $addButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-light-border ui-btn-sm ui-btn-icon-add adminkit-json-add" data-group="{$groupId}">{$addLabel}</button>
            HTML;

        return <<<HTML
        <div{$wrapperAttrs} id="{$groupId}" data-json-group="{$groupId}" data-json-template="{$templateJson}"{$maxAttr}{$sortableAttr}>
            <div class="adminkit-json-table">
                {$headerHtml}
                <div class="adminkit-json-rows">{$rowsHtml}</div>
            </div>
            {$addButton}
        </div>
        {$this->renderScriptOnce()}
        {$this->renderStyleOnce()}
        HTML;
    }

    /** @param array<string,mixed> $formData */
    protected function renderSingle(mixed $value, array $formData): string
    {
        $object = $this->parseObject($this->resolveOwnValue($value));
        $readonly = $this->isReadOnlyFor($formData);

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-json');
        $label = htmlspecialcharsbx($this->label);
        $hint = $this->renderHint();

        if ($this->isStacked()) {
            $cellsHtml = '';
            foreach ($this->schema as $subField) {
                $subColumn = $subField->getColumn();
                $cellsHtml .= $this->renderStackCell(
                    $subField,
                    "{$this->column}[{$subColumn}]",
                    $object[$subColumn] ?? null,
                );
            }

            return <<<HTML
            <div{$wrapperAttrs} id="adminkit_json_{$this->column}">
                <div class="adminkit-json-label">{$label}{$hint}</div>
                <div class="adminkit-json-row adminkit-json-row--stack">{$cellsHtml}</div>
            </div>
            {$this->renderStyleOnce()}
            HTML;
        }

        $gridStyle = $this->gridTemplateColumns(false);

        $cellsHtml = '';
        foreach ($this->schema as $subField) {
            $subColumn = $subField->getColumn();
            $originalColumn = $this->fieldColumn($subField);
            $this->setFieldColumn($subField, "{$this->column}[{$subColumn}]");

            $cellsHtml .= '<div class="adminkit-json-cell">'
                . $subField->renderForm($object[$subColumn] ?? null)
                . '</div>';

            $this->setFieldColumn($subField, $originalColumn);
        }

        return <<<HTML
        <div{$wrapperAttrs} id="adminkit_json_{$this->column}">
            <div class="adminkit-json-label">{$label}{$hint}</div>
            <div class="adminkit-json-table">
                <div class="adminkit-json-header" style="{$gridStyle}">{$this->renderHeader(false)}</div>
                <div class="adminkit-json-row" style="{$gridStyle}">{$cellsHtml}</div>
            </div>
        </div>
        {$this->renderStyleOnce()}
        HTML;
    }

    /**
     * Renders one sub-field as a stacked cell: its own label above the control.
     * The sub-field's POST column is temporarily rewritten to the positional
     * path, mirroring {@see renderRow()}.
     */
    protected function renderStackCell(FieldContract $subField, string $name, mixed $value): string
    {
        $originalColumn = $this->fieldColumn($subField);
        $this->setFieldColumn($subField, $name);
        $control = $subField->renderForm($value);
        $this->setFieldColumn($subField, $originalColumn);

        $label = htmlspecialcharsbx($subField->getLabel());
        $labelHtml = $label !== '' ? '<div class="adminkit-json-cell-label">' . $label . '</div>' : '';

        return '<div class="adminkit-json-cell adminkit-json-cell--stack">' . $labelHtml . $control . '</div>';
    }

    protected function gridTemplateColumns(bool $withActions = true): string
    {
        $trailing = $withActions ? ' auto' : '';
        $leading = $this->multiple && $this->sortable && $withActions ? 'auto ' : '';

        $tracks = [];
        foreach ($this->schema as $subField) {
            $width = method_exists($subField, 'getColumnWidth') ? $subField->getColumnWidth() : null;
            $tracks[] = $width !== null ? ($width . 'px') : 'minmax(0, 1fr)';
        }

        return 'grid-template-columns: ' . $leading . implode(' ', $tracks) . $trailing . ';';
    }

    protected function renderHeader(bool $multiple): string
    {
        $cellsHtml = '';
        if ($multiple && $this->sortable) {
            $cellsHtml .= '<div class="adminkit-json-header-cell adminkit-json-header-cell--handle"></div>';
        }

        foreach ($this->schema as $subField) {
            $cellsHtml .= '<div class="adminkit-json-header-cell">' . htmlspecialcharsbx($subField->getLabel()) . '</div>';
        }

        if ($multiple) {
            $cellsHtml .= '<div class="adminkit-json-header-cell adminkit-json-header-cell--action"></div>';
        }

        return $cellsHtml;
    }

    /**
     * @param array<string,mixed> $row
     */
    protected function renderRow(string $groupId, int|string $index, array $row, bool $readonly): string
    {
        $removeButton = $readonly
            ? ''
            : <<<HTML
            <button type="button" class="ui-btn ui-btn-icon-remove ui-btn-link ui-btn-sm adminkit-json-remove" title="Удалить" data-group="{$groupId}"></button>
            HTML;

        if ($this->isStacked()) {
            return $this->renderStackedRow($index, $row, $readonly, $removeButton);
        }

        $cellsHtml = '';

        if ($this->sortable && !$readonly) {
            $cellsHtml .= '<div class="adminkit-json-cell adminkit-json-cell--handle">'
                . '<span class="adminkit-json-handle" draggable="true" title="Перетащите для сортировки">⠿</span>'
                . '</div>';
        } elseif ($this->sortable) {
            $cellsHtml .= '<div class="adminkit-json-cell adminkit-json-cell--handle"></div>';
        }

        foreach ($this->schema as $subField) {
            $subColumn = $subField->getColumn();
            $originalColumn = $this->fieldColumn($subField);
            $this->setFieldColumn($subField, "{$this->column}[{$index}][{$subColumn}]");

            $cellsHtml .= '<div class="adminkit-json-cell">'
                . $subField->renderForm($row[$subColumn] ?? null)
                . '</div>';

            $this->setFieldColumn($subField, $originalColumn);
        }

        $gridStyle = ' style="' . $this->gridTemplateColumns() . '"';

        return <<<HTML
        <div class="adminkit-json-row" data-json-row{$gridStyle}>
            {$cellsHtml}
            <div class="adminkit-json-cell adminkit-json-cell--action">{$removeButton}</div>
        </div>
        HTML;
    }

    /**
     * Renders one row with sub-fields stacked vertically. Drag handle and the
     * remove button share a small toolbar above the fields; the row keeps the
     * same `data-json-row` / control-name contract as the grid layout so the
     * shared add/remove/reorder JS works unchanged.
     *
     * @param array<string,mixed> $row
     */
    protected function renderStackedRow(int|string $index, array $row, bool $readonly, string $removeButton): string
    {
        $handle = ($this->sortable && !$readonly)
            ? '<span class="adminkit-json-handle" draggable="true" title="Перетащите для сортировки">⠿</span>'
            : '';

        $head = ($handle !== '' || $removeButton !== '')
            ? '<div class="adminkit-json-stack-head">' . $handle . '<span class="adminkit-json-stack-spacer"></span>' . $removeButton . '</div>'
            : '';

        $cellsHtml = '';
        foreach ($this->schema as $subField) {
            $subColumn = $subField->getColumn();
            $cellsHtml .= $this->renderStackCell(
                $subField,
                "{$this->column}[{$index}][{$subColumn}]",
                $row[$subColumn] ?? null,
            );
        }

        return <<<HTML
        <div class="adminkit-json-row adminkit-json-row--stack" data-json-row>
            {$head}
            {$cellsHtml}
        </div>
        HTML;
    }

    protected function fieldColumn(FieldContract $field): string
    {
        return $field->getColumn();
    }

    /**
     * Temporarily rewrites a sub-field's POST column to a positional path so
     * nested values round-trip through the standard {@see \Bitrix\Main\Request}
     * array parsing.
     */
    protected function setFieldColumn(FieldContract $field, string $column): void
    {
        $ref = new \ReflectionProperty($field, 'column');
        $ref->setAccessible(true);
        $ref->setValue($field, $column);
    }

    protected function renderScriptOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <script>
        (function () {
            if (window.__adminKitJsonInit) { return; }
            window.__adminKitJsonInit = true;

            function renumber(group) {
                var rows = group.querySelectorAll(':scope > .adminkit-json-table > .adminkit-json-rows > [data-json-row]');
                rows.forEach(function (row, index) {
                    row.querySelectorAll('[name]').forEach(function (el) {
                        el.name = el.name.replace(/\[(\d+|__INDEX__)\]/, '[' + index + ']');
                    });
                });
            }

            document.addEventListener('click', function (e) {
                var addBtn = e.target.closest('.adminkit-json-add');
                if (addBtn) {
                    var group = document.getElementById(addBtn.dataset.group);
                    if (!group) { return; }
                    var maxRows = parseInt(group.dataset.maxRows || '0', 10);
                    var rowsWrap = group.querySelector(':scope > .adminkit-json-table > .adminkit-json-rows');
                    var currentCount = rowsWrap.querySelectorAll(':scope > [data-json-row]').length;
                    if (maxRows > 0 && currentCount >= maxRows) { return; }

                    var template = JSON.parse(group.dataset.jsonTemplate);
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

                var removeBtn = e.target.closest('.adminkit-json-remove');
                if (removeBtn) {
                    var row = removeBtn.closest('[data-json-row]');
                    var groupEl = document.getElementById(removeBtn.dataset.group);
                    if (row && groupEl) {
                        row.remove();
                        renumber(groupEl);
                    }
                }
            });

            var dragged = null;

            document.addEventListener('dragstart', function (e) {
                var handle = e.target.closest('.adminkit-json-handle');
                if (!handle) { return; }
                dragged = handle.closest('[data-json-row]');
                if (dragged) {
                    dragged.classList.add('adminkit-json-row--dragging');
                    e.dataTransfer.effectAllowed = 'move';
                }
            });

            document.addEventListener('dragend', function () {
                if (!dragged) { return; }
                dragged.classList.remove('adminkit-json-row--dragging');
                var group = dragged.closest('[data-json-group]');
                dragged = null;
                if (group) { renumber(group); }
            });

            document.addEventListener('dragover', function (e) {
                if (!dragged) { return; }
                var rowsWrap = dragged.parentElement;
                var over = e.target.closest('[data-json-row]');
                if (!over || over === dragged || over.parentElement !== rowsWrap) { return; }
                e.preventDefault();

                var rect = over.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                rowsWrap.insertBefore(dragged, after ? over.nextElementSibling : over);
            });
        })();
        </script>
        HTML;
    }

    protected function renderStyleOnce(): string
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        return <<<'HTML'
        <style>
        .adminkit-json-table {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
            border: 1px solid #e1e7ec;
            border-radius: var(--ui-border-radius-md, 8px);
            padding: 10px 12px;
        }
        .adminkit-json-add {
            margin-top: 10px;
        }
        .adminkit-json-header {
            padding-top: 10px;
        }
        .adminkit-json-rows {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .adminkit-json-rows:empty::after {
            content: "Пусто";
            display: block;
            padding: 16px;
            text-align: center;
            color: var(--ui-color-base-70, #828b95);
            font-size: 13px;
        }
        .adminkit-json-row {
            background: var(--ui-color-palette-white-base, #fff); 
        }
        .adminkit-json-header,
        .adminkit-json-row {
            display: grid;
            gap: 8px;
            align-items: center;
        }
        .adminkit-json-row--stack {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            border: 1px solid #e1e7ec;
            border-radius: var(--ui-border-radius-md, 8px);
            padding: 10px 12px;
        }
        .adminkit-json-stack-head {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .adminkit-json-stack-spacer {
            flex: 1 1 auto;
        }
        .adminkit-json-cell--stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .adminkit-json-cell-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--ui-color-base-70, #828b95);
        }
        .adminkit-json-header-cell {
            font-size: 12px;
            font-weight: 600;
            color: var(--ui-color-base-70, #828b95);
            text-transform: uppercase;
        }
        .adminkit-json-header-cell--action,
        .adminkit-json-header-cell--handle,
        .adminkit-json-cell--action,
        .adminkit-json-cell--handle {
            min-width: 24px;
        }
        .adminkit-json-cell .ui-ctl,
        .adminkit-json-cell .ui-ctl-w100 {
            width: 100%;
        }
        .adminkit-json-handle {
            cursor: grab;
            user-select: none;
            color: var(--ui-color-base-70, #828b95);
            font-size: 16px;
            line-height: 1;
        }
        .adminkit-json-handle:active {
            cursor: grabbing;
        }
        .adminkit-json-row--dragging {
            opacity: 0.5;
        }
        @media (max-width: 782px) {
            .adminkit-json-header {
                display: none;
            }
            .adminkit-json-row {
                grid-template-columns: 1fr !important;
            }
        }
        </style>
        HTML;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    protected function parseRows(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $this->isListOfRows($value) ? array_values($value) : [];
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || ($value[0] !== '[' && $value[0] !== '{')) {
                return [];
            }

            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }

            return is_array($decoded) && $this->isListOfRows($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseObject(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $this->isAssociativeRow($value) ? $value : [];
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || ($value[0] !== '{' && $value[0] !== '[')) {
                return [];
            }

            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }

            return is_array($decoded) && $this->isAssociativeRow($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param array<array-key, mixed> $value
     */
    protected function isListOfRows(array $value): bool
    {
        foreach ($value as $row) {
            if (!is_array($row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    protected function isAssociativeRow(array $value): bool
    {
        foreach ($value as $item) {
            if (is_array($item)) {
                return false;
            }
        }

        return true;
    }

    public function normalize(mixed $value): mixed
    {
        return $this->multiple ? $this->normalizeRows($value) : $this->normalizeObject($value);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    protected function normalizeRows(mixed $value): array
    {
        $rows = is_array($value) ? $value : [];
        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $isEmptyRow = true;
            $normalizedRow = $this->normalizeSingleRow($row, $isEmptyRow);

            if (!$isEmptyRow) {
                $result[] = $normalizedRow;
            }
        }

        return array_values($result);
    }

    /**
     * @return array<string,mixed>
     */
    protected function normalizeObject(mixed $value): array
    {
        $row = is_array($value) ? $value : [];
        $isEmptyRow = true;

        return $this->normalizeSingleRow($row, $isEmptyRow);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    protected function normalizeSingleRow(array $row, bool &$isEmptyRow): array
    {
        $normalizedRow = [];
        $isEmptyRow = true;

        foreach ($this->schema as $subField) {
            $subColumn = $subField->getColumn();
            $rawValue = $row[$subColumn] ?? null;
            $normalizedValue = $subField->serializePostValue($rawValue);
            $normalizedRow[$subColumn] = $normalizedValue;

            if ($normalizedValue !== null && $normalizedValue !== '' && $normalizedValue !== []) {
                $isEmptyRow = false;
            }
        }

        return $normalizedRow;
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    public function serializeOptionValue(mixed $value): string
    {
        $data = is_array($value) ? $value : [];

        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
            return $this->multiple ? '[]' : '{}';
        }

        if (!$this->multiple && $data === []) {
            return '{}';
        }

        return $encoded;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function unserializeOptionValue(string $value): array
    {
        return $this->multiple ? $this->parseRows($value) : $this->parseObject($value);
    }

    public function previewValue(mixed $value): string
    {
        if ($this->multiple) {
            return (string)count($this->parseRows($value));
        }

        return $this->parseObject($value) === [] ? '' : '1';
    }

    public function runValidation(mixed $value, array $data = []): array
    {
        $errors = parent::runValidation($value, $data);

        if (!$this->multiple) {
            $row = is_array($value) ? $value : [];
            foreach ($this->schema as $subField) {
                $subColumn = $subField->getColumn();
                foreach ($subField->runValidation($row[$subColumn] ?? null, $row) as $subError) {
                    $errors[] = $subError;
                }
            }

            return $errors;
        }

        $rows = is_array($value) ? $value : [];
        if ($this->minRows > 0 && count($rows) < $this->minRows) {
            $errors[] = "Поле \"{$this->label}\" должно содержать минимум {$this->minRows} строк(и).";
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($this->schema as $subField) {
                $subColumn = $subField->getColumn();
                foreach ($subField->runValidation($row[$subColumn] ?? null, $row) as $subError) {
                    $errors[] = $subError;
                }
            }
        }

        return $errors;
    }
}
