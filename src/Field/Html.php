<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Html extends Field
{
    protected int $rows = 15;
    protected bool $useEditor = true;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function disableEditor(bool $disable = true): static
    {
        $this->useEditor = !$disable;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = (string)($this->resolveValue($value) ?? '');
        $name = htmlspecialcharsbx($this->column);
        $escapedValue = htmlspecialcharsbx($currentValue);

        if (!$this->useEditor) {
            return <<<HTML
            <div class="ui-ctl ui-ctl-textarea">
                <textarea class="ui-ctl-element" name="{$name}" rows="{$this->rows}">{$escapedValue}</textarea>
            </div>
            HTML;
        }

        global $APPLICATION;

        ob_start();
        $APPLICATION->IncludeComponent('bitrix:main.html.editor', '', [
            'EDITOR_LANG' => LANGUAGE_ID,
            'VARIABLE_NAME' => $name,
            'VALUE' => $currentValue,
            'ROWS' => $this->rows,
            'USE_EDITOR' => 'Y',
            'ALLOW_PHP' => 'N',
        ]);

        return ob_get_clean() ?: <<<HTML
        <div class="ui-ctl ui-ctl-textarea">
            <textarea class="ui-ctl-element" name="{$name}" rows="{$this->rows}">{$escapedValue}</textarea>
        </div>
        HTML;
    }

    public function previewValue(mixed $value): string
    {
        return (string)$value;
    }
}
