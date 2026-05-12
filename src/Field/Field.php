<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Field\Traits\HasFormat;
use MB\Bitrix\AdminKit\Field\Traits\HasReactivity;
use MB\Bitrix\AdminKit\Field\Traits\HasValidation;
use MB\Bitrix\AdminKit\Field\Traits\HasVisibility;
use MB\Bitrix\AdminKit\Field\Traits\Makeable;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

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

    public function __construct(string $label, ?string $column = null)
    {
        $this->label = $label;
        $this->column = $column ?? mb_strtoupper($label);
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

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function getGridColumnConfig(): array
    {
        return [
            'id' => $this->column,
            'name' => $this->label,
            'sort' => $this->sortable ? $this->column : false,
            'default' => true,
            'type' => $this->getGridColumnType(),
            'editable' => $this->editable,
        ];
    }

    public function getFilterType(): ?string
    {
        return null;
    }

    public function getFieldAssembler(): ?FieldAssembler
    {
        return null;
    }

    abstract public function renderFormField(mixed $value = null): string;

    protected function resolveValue(mixed $value = null): mixed
    {
        return $value ?? $this->value ?? $this->default;
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
        return false;
    }

    /**
     * Normalize a raw POST value to a storable scalar string.
     * Override in subclasses that store multi-value data (e.g. EntitySelect).
     *
     * Default: join arrays with commas; pass scalars through unchanged.
     */
    public function serializePostValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return implode(',', array_filter(array_map('strval', $value)));
        }

        return $value;
    }

    protected ?array $visibleWhenRule = null;

    /**
     * Show this field only when another field has the given value.
     * $value may be a single scalar or an array of accepted values.
     * The check is pure JS (display:none toggle) — no AJAX needed.
     */
    public function visibleWhen(string $column, mixed $value): static
    {
        $this->visibleWhenRule = is_array($value)
            ? ['column' => $column, 'values' => array_map('strval', $value)]
            : ['column' => $column, 'value' => (string)$value];

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

        return '<span class="ui-hint" data-hint="' . htmlspecialcharsbx($this->hint) . '"><span class="ui-hint-icon"></span></span>';
    }

    protected function renderRequired(): string
    {
        return $this->required ? '<span class="ui-ctl-required">*</span>' : '';
    }
}
