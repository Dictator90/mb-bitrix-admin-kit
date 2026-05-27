<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Field\Concerns\HasCheckedValues;

class Checkbox extends Field
{
    use HasCheckedValues;

    public function getGridColumnType(): string
    {
        return 'checkbox';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $checkedVal = htmlspecialcharsbx($this->checkedValue);
        $uncheckedVal = htmlspecialcharsbx($this->uncheckedValue);
        $label = htmlspecialcharsbx($this->label);
        $checked = $this->isCheckedState($currentValue) ? ' checked' : '';
        $readonlyAttr = $this->formReadonlyAttr();

        return <<<HTML
        <input type="hidden" name="{$name}" value="{$uncheckedVal}">
        <label class="ui-ctl ui-ctl-checkbox">
            <input type="checkbox" class="ui-ctl-element" name="{$name}" value="{$checkedVal}"{$checked}{$readonlyAttr}>
            <div class="ui-ctl-label-text">{$label}</div>
        </label>
        HTML;
    }
}
