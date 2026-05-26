<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Email extends Field
{
    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $val = $this->escapedFormValue($value);
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $placeholderAttr = $this->placeholderAttr();
        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="email" class="ui-ctl-element" name="{$name}" value="{$val}"{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>
        </div>
        HTML;
    }
}
