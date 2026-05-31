<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Field\Concerns\HasCheckedValues;
use MB\Bitrix\AdminKit\Grid\Row\Assembler\SwitcherAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class Switcher extends Field
{
    use HasCheckedValues;

    public function getFieldAssembler(): ?FieldAssembler
    {
        return new SwitcherAssembler([$this->column], $this->checkedValue);
    }

    public function getGridColumnType(): string
    {
        return 'checkbox';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);

        if ($this->isReadOnlyFor($this->renderFormData)) {
            return $this->renderReadOnly($name, $currentValue);
        }

        Extension::load('ui.switcher');

        $isChecked = $this->isCheckedState($currentValue) ? 'true' : 'false';
        $checkedVal = htmlspecialcharsbx($this->checkedValue);
        $uncheckedVal = htmlspecialcharsbx($this->uncheckedValue);
        $inputId = 'switcher_' . $name . '_' . uniqid();

        return <<<HTML
            <div id="{$inputId}"></div>
            <script>
            BX.ready(function() {
                new BX.UI.Switcher({
                    node: document.getElementById('{$inputId}'),
                    checked: {$isChecked},
                    inputName: '{$name}',
                    inputValue: '{$checkedVal}',
                    inputValueOff: '{$uncheckedVal}'
                })
            });
            </script>
        HTML;
    }

    private function renderReadOnly(string $name, mixed $currentValue): string
    {
        $checked = $this->isCheckedState($currentValue);
        $stored = htmlspecialcharsbx($checked ? $this->checkedValue : $this->uncheckedValue);
        $checkedAttr = $checked ? ' checked' : '';

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-checkbox');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <input type="hidden" name="{$name}" value="{$stored}">
        <label{$wrapperAttrs}>
            <input type="checkbox"{$elementAttrs} disabled{$checkedAttr}>
        </label>
        HTML;
    }
}
