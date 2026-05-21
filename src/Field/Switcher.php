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

    public static function isCheckedValue(mixed $value, string $checkedValue = 'Y'): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value > 0;
        }

        $normalized = strtoupper((string) $value);

        return $normalized === strtoupper($checkedValue)
            || $normalized === '1'
            || $normalized === 'TRUE';
    }

    public function isCheckedState(mixed $value): bool
    {
        return self::isCheckedValue($value, $this->checkedValue);
    }

    public function normalize(mixed $value): mixed
    {
        return $this->isCheckedState($value) ? $this->checkedValue : $this->uncheckedValue;
    }

    public function serializePostValue(mixed $value): mixed
    {
        return $this->normalize($value);
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
}
