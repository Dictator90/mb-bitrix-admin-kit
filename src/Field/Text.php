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
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);
        $maxAttr = $this->maxLength ? ' maxlength="' . $this->maxLength . '"' : '';
        $reqAttr = $this->required ? ' required' : '';
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $placeholderAttr = $this->placeholder !== null ? ' placeholder="' . htmlspecialcharsbx($this->placeholder) . '"' : '';
        $reactiveAttrs = $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="text" class="ui-ctl-element" name="{$name}" value="{$val}"{$maxAttr}{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>
        </div>
        HTML;
    }
}
