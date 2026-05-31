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

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-checkbox');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <input type="hidden" name="{$name}" value="{$uncheckedVal}">
        <label{$wrapperAttrs}>
            <input type="checkbox"{$elementAttrs} name="{$name}" value="{$checkedVal}"{$checked}{$readonlyAttr}>
            <div class="ui-ctl-label-text">{$label}</div>
        </label>
        HTML;
    }
}
