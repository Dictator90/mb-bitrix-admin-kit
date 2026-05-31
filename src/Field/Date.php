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

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-after-icon', 'ui-ctl-date');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <div class="ui-ctl-after ui-ctl-icon-calendar"></div>
            <input type="text"{$elementAttrs} id="{$inputId}" name="{$name}" value="{$val}"{$reqAttr}{$readonlyAttr}{$reactiveAttrs} onclick="BX.calendar({node: this, field: this, bTime: false})">
        </div>
        HTML;
    }
}
