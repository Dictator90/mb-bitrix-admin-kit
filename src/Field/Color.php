<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Color extends Field
{
    protected string $defaultColor = '#000000';

    public function defaultColor(string $hex): static
    {
        $this->defaultColor = $hex;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value) ?? $this->defaultColor;
        $name = htmlspecialcharsbx($this->column);
        $escapedValue = htmlspecialcharsbx((string)$currentValue);
        $inputId = 'color_' . $name . '_' . uniqid();
        $readonlyAttr = $this->formReadonlyAttr();
        $pickerDisabled = $readonlyAttr !== '' ? ' disabled' : '';

        return <<<HTML
        <div class="adminkit-color-field">
            <input type="color" id="{$inputId}" class="adminkit-color-swatch" value="{$escapedValue}"{$pickerDisabled}
                oninput="document.getElementById('{$inputId}_text').value = this.value">
            <div class="ui-ctl ui-ctl-textbox adminkit-color-text">
                <input type="text" id="{$inputId}_text" name="{$name}" class="ui-ctl-element"
                    value="{$escapedValue}" maxlength="7" placeholder="#000000"{$readonlyAttr}
                    oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('{$inputId}').value=this.value}">
            </div>
        </div>
        HTML;
    }

    protected function previewReturnsHtml(): bool
    {
        return true;
    }

    public function previewValue(mixed $value): string
    {
        $hex = htmlspecialcharsbx((string)$value);
        if (!$hex) {
            return '';
        }

        return '<span class="adminkit-color-swatch-preview" style="background:' . $hex . '"></span>' . $hex;
    }
}
