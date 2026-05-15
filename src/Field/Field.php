<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Closure;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Field\Traits\HasFormat;
use MB\Bitrix\AdminKit\Field\Traits\HasReactivity;
use MB\Bitrix\AdminKit\Field\Traits\HasValidation;
use MB\Bitrix\AdminKit\Field\Traits\HasVisibility;
use MB\Bitrix\AdminKit\Field\Traits\Makeable;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Support\Conditionable\ConditionTree;

abstract class Field implements FieldContract
{
    use Makeable;
    use HasVisibility;
    use HasValidation;
    use HasFormat;
    use HasReactivity;

    protected string $column;
    protected string $label;
    protected mixed $value = null;
    protected mixed $default = null;
    protected bool $sortable = true;
    protected bool $editable = false;
    protected ?string $hint = null;
    protected bool $readonly = false;
    protected bool $exportable = true;
    protected bool $importable = true;
    protected bool $private = false;
    protected bool $system = false;
    protected bool $asEditLink = false;

    /** @var array<int, \Closure|ConditionTree|array<string,mixed>> */
    protected array $readonlyWhen = [];
    protected bool $multiple = false;
    protected ?string $help = null;
    protected ?string $placeholder = null;
    protected ?Closure $computedCallback = null;
    protected ?Closure $displayCallback = null;

    public function __construct(string $label, ?string $column = null)
    {
        $this->label = $label;
        $this->column = $column ?? AdminString::safeKey($label);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function fill(mixed $value): static
    {
        return $this->setValue($value);
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function editable(bool $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function hint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function help(?string $text): static
    {
        $this->help = $text;
        $this->hint = $text;

        return $this;
    }

    public function placeholder(?string $text): static
    {
        $this->placeholder = $text;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function exportable(bool $exportable = true): static
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function importable(bool $importable = true): static
    {
        $this->importable = $importable;

        return $this;
    }

    public function private(bool $private = true): static
    {
        $this->private = $private;

        return $this;
    }

    public function system(bool $system = true): static
    {
        $this->system = $system;

        return $this;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function isImportable(): bool
    {
        return $this->importable;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function readonlyWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static
    {
        $this->readonlyWhen[] = $this->normalizeCondition($condition, $operator, $value);

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function getGridColumnConfig(): array
    {
        return [
            'id' => AdminString::safeKey($this->column),
            'name' => $this->label,
            'sort' => (!$this->isComputed() && $this->sortable) ? $this->column : false,
            'default' => true,
            'type' => $this->getGridColumnType(),
            'editable' => $this->editable,
        ];
    }

    public function getFilterType(): ?string
    {
        return null;
    }

    public function asEditLink(bool $enabled = true): static
    {
        $this->asEditLink = $enabled;

        return $this;
    }

    public function linkToEdit(bool $enabled = true): static
    {
        return $this->asEditLink($enabled);
    }

    public function shouldRenderAsEditLink(): bool
    {
        return $this->asEditLink;
    }

    public function computed(Closure $callback): static
    {
        $this->computedCallback = $callback;
        $this->sortable(false);

        return $this;
    }

    public function isComputed(): bool
    {
        return $this->computedCallback instanceof Closure;
    }

    public function computeValue(array $row): mixed
    {
        if (!$this->computedCallback instanceof Closure) {
            return $row[$this->column] ?? null;
        }

        return ($this->computedCallback)($row);
    }

    public function displayUsing(Closure $callback): static
    {
        $this->displayCallback = $callback;

        return $this;
    }

    public function displayValue(mixed $value, array $row = [], array $context = []): mixed
    {
        if (!$this->displayCallback instanceof Closure) {
            return $value;
        }

        return ($this->displayCallback)($value, $row, $context);
    }

    public function getFieldAssembler(): ?FieldAssembler
    {
        return null;
    }

    abstract public function renderFormField(mixed $value = null): string;

    public function resolveValue(mixed $item, array $row = []): mixed
    {
        if (is_array($item)) {
            return $item[$this->column] ?? $row[$this->column] ?? $this->value ?? $this->default;
        }

        if (is_object($item) && method_exists($item, 'get')) {
            return $item->get($this->column) ?? $row[$this->column] ?? $this->value ?? $this->default;
        }

        return $item ?? $row[$this->column] ?? $this->value ?? $this->default;
    }

    protected function renderReactiveAttrs(): string
    {
        if (!$this->reactive) {
            return '';
        }

        $targets = array_keys($this->onChangeCallbacks);
        $targetsJson = htmlspecialcharsbx(json_encode($targets));
        $column = htmlspecialcharsbx($this->column);

        return ' data-reactive="1" data-reactive-field="' . $column . '" data-reactive-targets="' . $targetsJson . '"';
    }

    public function isReadOnly(): bool
    {
        return $this->readonly;
    }

    public function isReadOnlyFor(array $data = []): bool
    {
        if ($this->readonly) {
            return true;
        }

        foreach ($this->readonlyWhen as $condition) {
            if ($this->evaluateFieldCondition($condition, $data)) {
                return true;
            }
        }

        return false;
    }

    public function renderIndex(mixed $context, array $row = []): string
    {
        if ($context instanceof FieldRenderContext) {
            $row = $context->row;
            $value = $context->value;
            $meta = array_merge($context->meta, ['page' => $context->page, 'field' => $this, 'context' => $context]);
        } else {
            $value = $context;
            $meta = ['page' => 'index', 'field' => $this];
        }

        $displayValue = $this->displayValue($value, $row, $meta);

        return htmlspecialcharsbx((string)($this->previewValue($displayValue) ?? ''));
    }

    public function renderForm(mixed $context = null, array $data = []): string
    {
        $value = $context instanceof FieldRenderContext ? $context->value : $context;

        return $this->renderFormField($value);
    }

    public function renderDetail(mixed $context, array $row = []): string
    {
        if ($context instanceof FieldRenderContext) {
            $row = $context->row;
            $value = $context->value;
            $meta = array_merge($context->meta, ['page' => $context->page, 'field' => $this, 'context' => $context]);
        } else {
            $value = $context;
            $meta = ['page' => 'detail', 'field' => $this];
        }

        $displayValue = $this->displayValue($value, $row, $meta);

        return htmlspecialcharsbx((string)($this->previewValue($displayValue) ?? ''));
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            return is_array($value) ? array_values($value) : ($value === null ? [] : [$value]);
        }

        if (is_array($value)) {
            $first = reset($value);
            return $first === false && $value === [] ? null : $first;
        }

        return $value;
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    protected ?array $visibleWhenRule = null;

    /**
     * Show this field only when another field satisfies a condition.
     *
     * Shorthand (matches Box::visibleWhen API):
     *   ->visibleWhen('COLUMN', 'Y')         — equals Y
     *   ->visibleWhen('COLUMN', ['a', 'b'])  — in array
     *
     * Full form with explicit operator:
     *   ->visibleWhen('COLUMN', '=', 'Y')
     *   ->visibleWhen('COLUMN', '!=', 'Y')
     *   ->visibleWhen('COLUMN', 'in', ['a', 'b'])
     */
    public function visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static
    {
        // Support 2-arg shorthand: visibleWhen('column', 'value') — same as Box::visibleWhen().
        // Detect when the second arg is a value (not a recognised operator keyword) and shift args.
        if (is_string($condition) && $value === null && $operator !== null
                && !in_array($operator, ['=', '!=', 'in', 'not in'], true)) {
            $value    = $operator;
            $operator = '=';
        }

        $normalized = $this->normalizeCondition($condition, $operator, $value);
        if (is_array($normalized)) {
            $this->visibleWhenRule = is_array($normalized['value'])
                ? ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'values' => array_map('strval', $normalized['value'])]
                : ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'value' => (string)$normalized['value']];
        } else {
            $this->visibleWhenRule = null;
        }

        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }

    public function renderHint(): string
    {
        if ($this->hint === null) {
            return '';
        }

        return '<span class="ui-hint" data-hint="' . htmlspecialcharsbx(
            $this->hint
        ) . '"><span class="ui-hint-icon"></span></span>';
    }

    protected function renderRequired(): string
    {
        return $this->required ? '<span class="ui-ctl-required">*</span>' : '';
    }
}
