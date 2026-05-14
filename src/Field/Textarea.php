<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Textarea extends Field
{
    protected int $rows = 5;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->required ? ' required' : '';
        $readonlyAttr = $this->readonly ? ' readonly' : '';
        $placeholderAttr = $this->placeholder !== null ? ' placeholder="' . htmlspecialcharsbx($this->placeholder) . '"' : '';
        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-textarea">
            <textarea class="ui-ctl-element" name="{$name}" rows="{$this->rows}"{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>{$val}</textarea>
        </div>
        HTML;
    }
}
