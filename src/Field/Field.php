<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Field\Concerns\HasComputedValue;
use MB\Bitrix\AdminKit\Field\Concerns\HasEditLink;
use MB\Bitrix\AdminKit\Field\Concerns\HasFieldAttributes;
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
    use HasFieldAttributes;
    use HasEditLink;
    use HasComputedValue;

    protected bool $multiple = false;

    protected bool $selectable = true;

    /** @var string[]|null */
    protected ?array $selectColumns = null;

    /**
     * Form data captured by {@see renderForm()} so single-argument
     * {@see renderFormField()} overrides can still resolve readonly state.
     *
     * @var array<string,mixed>
     */
    protected array $renderFormData = [];

    public function __construct(?string $label = null, ?string $column = null)
    {
        $this->bootFieldIdentity($label, $column);
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    /** @param string[]|string|null $columns */
    public function selectColumns(array|string|null $columns): static
    {
        $this->selectColumns = is_string($columns) ? [$columns] : $columns;

        return $this;
    }

    /** @return string[] */
    public function getSelectColumns(): array
    {
        return $this->selectColumns ?? [$this->getColumn()];
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

        return $this->finalizePreview($this->previewValue($displayValue));
    }

    /**
     * Whether {@see previewValue()} returns ready HTML markup. When true,
     * {@see renderIndex()} / {@see renderDetail()} keep it as-is instead of
     * escaping it. Fields that build markup in previewValue() (Preview, Color,
     * Image) must escape their own dynamic values and return true here.
     */
    protected function previewReturnsHtml(): bool
    {
        return false;
    }

    private function finalizePreview(mixed $preview): string
    {
        $preview = (string)($preview ?? '');

        return $this->previewReturnsHtml() ? $preview : htmlspecialcharsbx($preview);
    }

    public function renderForm(mixed $context = null, array $data = []): string
    {
        if ($context instanceof FieldRenderContext) {
            $formData = $this->formDataFromRenderContext($context, $data);
            $this->renderFormData = $formData;

            /** @var mixed $self */
            $self = $this;
            return $self->renderFormField($context->value, $formData);
        }

        $this->renderFormData = $data;

        /** @var mixed $self */
        $self = $this;
        return $self->renderFormField($context, $data);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function formDataFromRenderContext(FieldRenderContext $context, array $data = []): array
    {
        $formData = array_merge($context->row, $data);
        $metaFormData = $context->meta['formData'] ?? null;
        if (is_array($metaFormData)) {
            $formData = array_merge($formData, $metaFormData);
        }

        $mode = $context->meta['mode'] ?? null;
        if (is_string($mode) && $mode !== '') {
            $formData['_mode'] = $mode;
        }

        $itemId = $context->item;
        if (is_object($itemId) && method_exists($itemId, 'getId')) {
            $id = $itemId->getId();
            if ($id !== null && $id !== '') {
                $formData['_id'] = (string)$id;
                $formData['ID'] ??= $id;
            }
        }

        return $formData;
    }

    /** @param array<string,mixed> $formData */
    protected function formReadonlyAttr(array $formData = []): string
    {
        if ($formData === []) {
            $formData = $this->renderFormData;
        }

        return $this->isReadOnlyFor($formData) ? ' readonly disabled' : '';
    }

    /** ` required` when the field is required, otherwise an empty string. */
    protected function requiredAttr(): string
    {
        return $this->required ? ' required' : '';
    }

    /** ` placeholder="…"` when a placeholder is set, otherwise an empty string. */
    protected function placeholderAttr(): string
    {
        return $this->placeholder !== null
            ? ' placeholder="' . htmlspecialcharsbx($this->placeholder) . '"'
            : '';
    }

    /** Resolve the field value for a form input and HTML-escape it. */
    protected function escapedFormValue(mixed $value): string
    {
        return htmlspecialcharsbx((string)$this->resolveValue($value));
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

        return $this->finalizePreview($this->previewValue($displayValue));
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
