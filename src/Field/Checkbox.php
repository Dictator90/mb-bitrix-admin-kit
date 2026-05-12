<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Checkbox extends Field
{
    protected string $checkedValue = 'Y';
    protected string $uncheckedValue = 'N';

    public function values(string $checked, string $unchecked): static
    {
        $this->checkedValue = $checked;
        $this->uncheckedValue = $unchecked;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'checkbox';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $checked = ((string)$currentValue === $this->checkedValue) ? ' checked' : '';

        return <<<HTML
        <input type="hidden" name="{$name}" value="{$this->uncheckedValue}">
        <label class="ui-ctl ui-ctl-checkbox">
            <input type="checkbox" class="ui-ctl-element" name="{$name}" value="{$this->checkedValue}"{$checked}>
            <div class="ui-ctl-label-text">{$this->label}</div>
        </label>
        HTML;
    }
}
