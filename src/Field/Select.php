<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Select extends Field
{
    protected array $options = [];

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'list';
    }

    public function getGridColumnConfig(): array
    {
        $config = parent::getGridColumnConfig();

        if ($this->editable) {
            $config['editable'] = ['items' => $this->options];
        }

        return $config;
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $multipleAttr = $this->multiple ? ' multiple' : '';
        $reqAttr = $this->required ? ' required' : '';

        if ($this->multiple) {
            $name .= '[]';
        }

        $optionsHtml = '';
        if ($this->placeholder) {
            $optionsHtml .= '<option value="">' . htmlspecialcharsbx($this->placeholder) . '</option>';
        }

        foreach ($this->options as $optValue => $optLabel) {
            $selected = $this->isSelected($optValue, $currentValue) ? ' selected' : '';
            $optionsHtml .= '<option value="' . htmlspecialcharsbx((string)$optValue) . '"' . $selected . '>'
                . htmlspecialcharsbx($optLabel) . '</option>';
        }

        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
            <select class="ui-ctl-element" name="{$name}"{$multipleAttr}{$reqAttr}{$reactiveAttrs}>{$optionsHtml}</select>
        </div>
        HTML;
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            return is_array($value) ? array_values($value) : ($value === null || $value === '' ? [] : [$value]);
        }

        return parent::normalize($value);
    }

    protected function isSelected(mixed $optValue, mixed $currentValue): bool
    {
        if (is_array($currentValue)) {
            return in_array($optValue, $currentValue);
        }

        return (string)$optValue === (string)$currentValue;
    }
}
