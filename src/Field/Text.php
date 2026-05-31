<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Text extends Field
{
    protected ?int $maxLength = null;

    public function maxLength(int $max, string $message = ''): static
    {
        $this->maxLength = $max;

        return parent::maxLength($max, $message);
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $val = $this->escapedFormValue($value);
        $name = htmlspecialcharsbx($this->column);
        $maxAttr = $this->maxLength ? ' maxlength="' . $this->maxLength . '"' : '';
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $placeholderAttr = $this->placeholderAttr();
        $reactiveAttrs = $this->renderReactiveAttrs();

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-textbox');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <input type="text"{$elementAttrs} name="{$name}" value="{$val}"{$maxAttr}{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>
        </div>
        HTML;
    }
}
