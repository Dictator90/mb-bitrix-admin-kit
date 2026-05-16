<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Field\Concerns\HasComputedValue;
use MB\Bitrix\AdminKit\Field\Concerns\HasEditLink;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldConditions;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldDefault;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldExport;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldFormatting;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldGridColumn;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldHelp;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldIdentity;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldImport;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldReactivity;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldReadonly;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldValidation;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldValue;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldVisibility;
use MB\Bitrix\AdminKit\Field\Concerns\Makeable;

abstract class Field implements FieldContract
{
    use Makeable;
    use HasFieldIdentity;
    use HasFieldValue;
    use HasFieldDefault;
    use HasFieldVisibility;
    use HasFieldValidation;
    use HasFieldFormatting;
    use HasFieldReactivity;
    use HasFieldGridColumn;
    use HasFieldExport;
    use HasFieldImport;
    use HasFieldReadonly;
    use HasFieldConditions;
    use HasFieldHelp;
    use HasEditLink;
    use HasComputedValue;

    protected bool $multiple = false;

    public function __construct(string $label, ?string $column = null)
    {
        $this->bootFieldIdentity($label, $column);
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    abstract public function renderFormField(mixed $value = null): string;

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

    /**
     * When true, OptionsPage keeps the stored option if POST value is empty
     * (e.g. password fields that are left blank on edit).
     */
    public function preserveStoredValueWhenEmpty(): bool
    {
        return false;
    }

}
