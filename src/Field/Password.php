<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class Password extends Field
{
    protected bool $showOldValue = true;

    public function __construct(?string $label = null, ?string $column = null)
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
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr();
        $stored = $value !== null ? (string)$value : '';
        $escapedValue = htmlspecialcharsbx($stored);
        $placeholder = htmlspecialcharsbx((string)($this->placeholder ?? ''));
        $placeholderAttr = $placeholder !== '' && $stored === '' ? ' placeholder="' . $placeholder . '"' : '';
        $toggleLabel = htmlspecialcharsbx(LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_PASSWORD_SHOW', 'Show password'));

        $toggle = <<<HTML
            <button type="button"
                id="toggle_{$this->id}"
                class="ui-ctl-after ui-ctl-icon-btn ui-ctl-icon-crossed-eye adminkit-password-toggle"
                data-adminkit-password-toggle="Y"
                aria-label="{$toggleLabel}"
                aria-pressed="false"></button>
        HTML;

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-textbox', 'ui-ctl-after-icon', 'adminkit-password-field');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <input id="{$this->id}" type="password"{$elementAttrs} name="{$name}" value="{$escapedValue}"{$reqAttr}{$readonlyAttr}{$placeholderAttr} autocomplete="off">
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
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr();
        $hasStored = $value !== null && (string)$value !== '';
        $keepHintPlaceholder = LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_PASSWORD_KEEP_PLACEHOLDER', 'Leave empty to keep current value');
        $placeholder = $hasStored
            ? htmlspecialcharsbx($this->placeholder ?? $keepHintPlaceholder)
            : htmlspecialcharsbx((string)($this->placeholder ?? ''));
        $placeholderAttr = $placeholder !== '' ? ' placeholder="' . $placeholder . '"' : '';
        $hint = $hasStored
            ? '<div class="ui-form-hint">' . htmlspecialcharsbx(LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_PASSWORD_KEEP_HINT', 'Stored value is preserved. Enter a new password only to replace it.')) . '</div>'
            : '';

        $wrapperAttrs = $this->renderWrapperAttributes('ui-ctl', 'ui-ctl-textbox');
        $elementAttrs = $this->renderElementAttributes('ui-ctl-element');

        return <<<HTML
        <div{$wrapperAttrs}>
            <input type="password"{$elementAttrs} name="{$name}" value=""{$reqAttr}{$readonlyAttr}{$placeholderAttr} autocomplete="new-password">
        </div>
        {$hint}
        HTML;
    }
}
