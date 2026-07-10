<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Renderers;

final class FieldRowRenderer
{
    public function render(FieldRowContext $context): string
    {
        $field = $context->field;
        $column = htmlspecialcharsbx($field->getColumn());
        $label = htmlspecialcharsbx($field->getLabel());
        $requiredMark = $field->isRequired() ? ' <span class="ui-ctl-required">*</span>' : '';
        $hint = method_exists($field, 'renderHint') ? $field->renderHint() : '';

        $rule = method_exists($field, 'getVisibleWhen') ? $field->getVisibleWhen() : null;
        $sourceVal = null;
        if ($rule !== null) {
            if (is_callable($context->sourceValueResolver)) {
                $sourceVal = ($context->sourceValueResolver)($rule['column'] ?? null);
            } elseif ($context->item !== null && isset($rule['column'])) {
                $sourceVal = $context->item->get((string)$rule['column']);
            }
        }

        $extraClass = '';
        $visibilityAttr = '';
        if ($rule !== null) {
            $visibilityAttr = ' data-visible-when="' . htmlspecialcharsbx((string)json_encode($rule)) . '"';
            if (!(new FieldVisibilityEvaluator())->isVisible($rule, $sourceVal)) {
                $extraClass = ' adminkit-conditional-hidden';
            }
        }

        $inner = $context->renderContext !== null
            ? $field->renderForm($context->renderContext)
            : $field->renderFormField($context->value);

        foreach ($context->errors as $message) {
            $inner .= '<div class="ui-alert ui-alert-inline ui-alert-xs ui-alert-danger adminkit-field-error"><span class="ui-alert-message">' . htmlspecialcharsbx($message) . '</span></div>';
        }

        $labelHtml = ($label !== '' || $requiredMark !== '' || $hint !== '')
            ? '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . $requiredMark . $hint . '</div></div>'
            : '';
        $rowClass = 'ui-form-row' . ($labelHtml === '' ? ' adminkit-form-row--no-label' : '') . $extraClass;

        return '<div class="' . $rowClass . '" data-field-column="' . $column . '"' . $visibilityAttr . '>'
            . $labelHtml
            . '<div class="ui-form-content">' . $inner . '</div>'
            . '</div>';
    }
}
