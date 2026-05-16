<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

class Password extends Field
{
    protected bool $showOldValue = true;

    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);
        $this->hideOn(PageType::INDEX, PageType::DETAIL);
    }

    /**
     * When enabled (default), the stored value is shown in the input with a show/hide toggle.
     * When disabled, the field stays empty on edit and empty submit keeps the stored value.
     */
    public function oldValue(bool $show = true): static
    {
        $this->showOldValue = $show;

        return $this;
    }

    public function showsOldValue(): bool
    {
        return $this->showOldValue;
    }

    public function preserveStoredValueWhenEmpty(): bool
    {
        return true;
    }

    public function renderFormField(mixed $value = null): string
    {
        if ($this->showsOldValue()) {
            return $this->renderFormFieldWithOldValue($value);
        }

        return $this->renderFormFieldWithoutOldValue($value);
    }

    protected function renderFormFieldWithOldValue(mixed $value): string
    {
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->required ? ' required' : '';
        $stored = $value !== null ? (string)$value : '';
        $escapedValue = htmlspecialcharsbx($stored);
        $placeholder = htmlspecialcharsbx((string)($this->placeholder ?? ''));
        $placeholderAttr = $placeholder !== '' && $stored === '' ? ' placeholder="' . $placeholder . '"' : '';

        $toggle = <<<HTML
            <button type="button"
                id="toggle_{$this->id}"
                class="ui-ctl-after ui-ctl-icon-btn ui-ctl-icon-crossed-eye adminkit-password-toggle"
                aria-label="Показать пароль"
                aria-pressed="false"></button>
        HTML;

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon adminkit-password-field">
            <input id="{$this->id}" type="password" class="ui-ctl-element" name="{$name}" value="{$escapedValue}"{$reqAttr}{$placeholderAttr} autocomplete="off">
            {$toggle}
        </div>
        <script>
            BX.ready(() => new MB.AdminKit.Fields.PasswordField({
                inputId: '{$this->id}',
                targetId: 'toggle_{$this->id}'
            }));
        </script>  
        HTML;
    }

    protected function renderFormFieldWithoutOldValue(mixed $value): string
    {
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->required ? ' required' : '';
        $hasStored = $value !== null && (string)$value !== '';
        $placeholder = $hasStored
            ? htmlspecialcharsbx($this->placeholder ?? 'Оставьте пустым, чтобы не менять')
            : htmlspecialcharsbx((string)($this->placeholder ?? ''));
        $placeholderAttr = $placeholder !== '' ? ' placeholder="' . $placeholder . '"' : '';
        $hint = $hasStored
            ? '<div class="ui-form-hint">Введите новый пароль только для замены.</div>'
            : '';

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="password" class="ui-ctl-element" name="{$name}" value=""{$reqAttr}{$placeholderAttr} autocomplete="new-password">
        </div>
        {$hint}
        HTML;
    }
}
