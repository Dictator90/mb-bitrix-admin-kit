<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

class Password extends Field
{
    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);
        $this->hideOn(PageType::INDEX, PageType::DETAIL);
    }

    public function renderFormField(mixed $value = null): string
    {
        $name = htmlspecialcharsbx($this->column);
        $reqAttr = $this->required ? ' required' : '';

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="password" class="ui-ctl-element" name="{$name}" value=""{$reqAttr} autocomplete="new-password">
        </div>
        HTML;
    }
}
