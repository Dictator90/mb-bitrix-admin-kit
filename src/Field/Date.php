<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Grid\Row\Assembler\DateAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class Date extends Field
{
    protected string $dateFormat = 'd.m.Y';

    public function dateFormat(string $format): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'date';
    }

    public function getFieldAssembler(): ?FieldAssembler
    {
        return new DateAssembler([$this->column], $this->dateFormat);
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $val = $this->escapedFormValue($value);
        $name = htmlspecialcharsbx($this->column);
        $inputId = 'date_' . $name . '_' . uniqid();
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
            <div class="ui-ctl-after ui-ctl-icon-calendar"></div>
            <input type="text" class="ui-ctl-element" id="{$inputId}" name="{$name}" value="{$val}"{$reqAttr}{$readonlyAttr}{$reactiveAttrs} onclick="BX.calendar({node: this, field: this, bTime: false})">
        </div>
        HTML;
    }
}
