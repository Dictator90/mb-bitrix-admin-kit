<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Grid\Row\Assembler\DateAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class DateTime extends Field
{
    protected string $dateFormat = 'd.m.Y H:i';

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

    public function renderFormField(mixed $value = null): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);
        $inputId = 'datetime_' . $name . '_' . uniqid();
        $reqAttr = $this->required ? ' required' : '';

        return <<<HTML
        <div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
            <div class="ui-ctl-after ui-ctl-icon-calendar"></div>
            <input type="text" class="ui-ctl-element" id="{$inputId}" name="{$name}" value="{$val}"{$reqAttr} onclick="BX.calendar({node: this, field: this, bTime: true})">
        </div>
        HTML;
    }
}
