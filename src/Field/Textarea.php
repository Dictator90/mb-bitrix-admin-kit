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
        <div class="ui-ctl ui-ctl-textarea">
            <textarea class="ui-ctl-element" name="{$name}" rows="{$this->rows}"{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>{$val}</textarea>
        </div>
        HTML;
    }
}
