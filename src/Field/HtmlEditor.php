<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Field\Concerns\HasHtmlEditor;

class HtmlEditor extends Textarea
{
    use HasHtmlEditor;

    protected int $rows = 15;

    protected bool $useEditor = true;

    public function disableEditor(bool $disable = true): static
    {
        $this->useEditor = !$disable;

        return $this;
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        if (!$this->useEditor || $this->formReadonlyAttr($formData) !== '') {
            return parent::renderFormField($value, $formData);
        }

        $currentValue = (string)($this->resolveValue($value) ?? '');
        $editorHtml = $this->renderHtmlEditor($this->column, $currentValue, $this->rows, $this->placeholder);

        if ($editorHtml !== '') {
            return '<div class="adminkit-html-editor">' . $editorHtml . '</div>';
        }

        return parent::renderFormField($value, $formData);
    }

    public function previewValue(mixed $value): string
    {
        return (string)$value;
    }

    protected function previewReturnsHtml(): bool
    {
        return true;
    }
}
