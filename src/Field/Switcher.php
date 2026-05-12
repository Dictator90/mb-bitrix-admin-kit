<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Grid\Row\Assembler\SwitcherAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class Switcher extends Field
{
    protected string $checkedValue = 'Y';
    protected string $uncheckedValue = 'N';

    public function getFieldAssembler(): ?FieldAssembler
    {
        return new SwitcherAssembler([$this->column], $this->checkedValue);
    }

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
        Extension::load('ui.switcher');

        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $isChecked = ((string)$currentValue === $this->checkedValue) ? 'true' : 'false';
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
}
