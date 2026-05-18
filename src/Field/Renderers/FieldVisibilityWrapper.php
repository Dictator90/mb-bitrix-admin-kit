<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Renderers;

final class FieldVisibilityWrapper
{
    public function wrap(string $html, ?array $rule, mixed $sourceValue): string
    {
        if ($rule === null) {
            return $html;
        }

        $json = htmlspecialcharsbx((string)json_encode($rule));
        $hidden = (new FieldVisibilityEvaluator())->isVisible($rule, $sourceValue) ? '' : ' adminkit-conditional-hidden';

        return '<div data-visible-when="' . $json . '" class="adminkit-visibility-wrapper' . $hidden . '">' . $html . '</div>';
    }
}
