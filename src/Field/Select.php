<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Closure;
use MB\Bitrix\AdminKit\Contracts\Field\OptionFieldContract;
use MB\Bitrix\AdminKit\Field\Options\OptionsResolverContract;
use MB\Bitrix\AdminKit\Field\Options\OptionsResolverFactory;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class Select extends Field implements OptionFieldContract
{
    /** @var array<mixed>|Closure|OptionsResolverContract */
    protected array|Closure|OptionsResolverContract $options = [];

    protected int $cacheTtl = 0;

    /** @param array<mixed>|Closure|OptionsResolverContract $options */
    public function options(array|Closure|OptionsResolverContract $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function cache(int $ttl): static
    {
        $this->cacheTtl = max(0, $ttl);

        return $this;
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /** @return array<mixed> */
    public function getOptions(array $context = []): array
    {
        return $this->resolveOptions($context);
    }

    /** @return array<mixed> */
    protected function resolveOptions(array $context = []): array
    {
        $resolver = (new OptionsResolverFactory())->make($this->options, $this->cacheTtl);

        return AdminCollection::make($resolver->resolve($context, $this))->all();
    }

    public function getGridColumnType(): string
    {
        return 'list';
    }

    public function getGridColumnConfig(): array
    {
        $config = parent::getGridColumnConfig();

        if ($this->editable) {
            $config['editable'] = ['items' => $this->getOptions()];
        }

        return $config;
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column . ($this->multiple ? '[]' : ''));
        $multipleAttr = $this->multiple ? ' multiple' : '';
        $reqAttr = $this->required ? ' required' : '';
        $disabledAttr = $this->readonly ? ' disabled' : '';
        $optionsHtml = '';

        if ($this->placeholder !== null && !$this->multiple) {
            $selected = $currentValue === null || $currentValue === '' ? ' selected' : '';
            $optionsHtml .= '<option value=""' . $selected . '>' . htmlspecialcharsbx($this->placeholder) . '</option>';
        }

        foreach ($this->getOptions() as $optValue => $optLabel) {
            $selected = $this->isSelected($optValue, $currentValue) ? ' selected' : '';
            $optionsHtml .= '<option value="' . htmlspecialcharsbx((string)$optValue) . '"' . $selected . '>'
                . htmlspecialcharsbx((string)$optLabel) . '</option>';
        }

        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
            <select class="ui-ctl-element" name="{$name}"{$multipleAttr}{$reqAttr}{$disabledAttr}{$reactiveAttrs}>{$optionsHtml}</select>
        </div>
        HTML;
    }

    public function renderIndex(mixed $value, array $row = []): string
    {
        if ($value instanceof FieldRenderContext) {
            $row = $value->row;
            $meta = array_merge($value->meta, ['page' => $value->page, 'field' => $this, 'context' => $value]);
            $value = $value->value;
        } else {
            $meta = ['page' => 'index', 'field' => $this];
        }

        return $this->renderSelectedLabels($this->displayValue($value, $row, $meta));
    }

    public function renderDetail(mixed $value, array $row = []): string
    {
        if ($value instanceof FieldRenderContext) {
            $row = $value->row;
            $meta = array_merge($value->meta, ['page' => $value->page, 'field' => $this, 'context' => $value]);
            $value = $value->value;
        } else {
            $meta = ['page' => 'detail', 'field' => $this];
        }

        return $this->renderSelectedLabels($this->displayValue($value, $row, $meta));
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            if ($value === null || $value === '') {
                return [];
            }

            return AdminCollection::make(is_array($value) ? $value : [$value])->all();
        }

        if (is_array($value)) {
            $first = reset($value);
            return $first === false ? null : $first;
        }

        return $value === '' ? null : $value;
    }

    public function runValidation(mixed $value, array $data = []): array
    {
        $errors = parent::runValidation($value, $data);
        $options = $this->getOptions($data);
        $values = $this->multiple ? (array)$this->normalize($value) : [$this->normalize($value)];

        $allowed = array_map('strval', array_keys($options));
        foreach ($values as $selected) {
            if ($selected === null || $selected === '') {
                continue;
            }

            if (!in_array((string)$selected, $allowed, true)) {
                $errors[] = LocalizedMessage::get(
                    __FILE__,
                    'MB_ADMIN_KIT_FIELD_INVALID_OPTION',
                    'Field "#FIELD#" contains an invalid value.',
                    ['#FIELD#' => $this->getLabel()],
                );
                break;
            }
        }

        return $errors;
    }

    protected function isSelected(mixed $optValue, mixed $currentValue): bool
    {
        if (is_array($currentValue)) {
            return in_array((string)$optValue, array_map('strval', $currentValue), true);
        }

        return (string)$optValue === (string)$currentValue;
    }

    protected function renderSelectedLabels(mixed $value): string
    {
        if (!is_array($value) && !is_object($value)) {
            $options = $this->getOptions();
            $label = $options[$value] ?? $value;

            return htmlspecialcharsbx((string)($label ?? ''));
        }

        $options = $this->getOptions();
        $labels = [];
        foreach (AdminCollection::make((array)$value)->all() as $item) {
            $labels[] = htmlspecialcharsbx((string)($options[$item] ?? $item));
        }

        return implode(', ', $labels);
    }

}
