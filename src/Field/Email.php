<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Validation\Rules;

class Email extends Field
{
    public function renderFormField(mixed $value = null): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->required ? ' required' : '';

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="email" class="ui-ctl-element" name="{$name}" value="{$val}"{$reqAttr}>
        </div>
        HTML;
    }
}
