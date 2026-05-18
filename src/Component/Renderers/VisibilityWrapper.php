<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Renderers;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Contracts\UI\ConditionalVisibilityContract;
use MB\Bitrix\AdminKit\Field\Renderers\FieldVisibilityEvaluator;

final class VisibilityWrapper
{
    public function wrap(string $inner, mixed $component, ComponentContext $context): string
    {
        if (!$component instanceof ConditionalVisibilityContract) {
            return $inner;
        }

        $rule = $component->getVisibleWhen();
        if ($rule === null) {
            return $inner;
        }

        $json = htmlspecialcharsbx((string)json_encode($rule));
        $sourceVal = $context->item?->get((string)($rule['column'] ?? ''));
        $hidden = (new FieldVisibilityEvaluator())->isVisible($rule, $sourceVal) ? '' : ' adminkit-conditional-hidden';

        return '<div data-visible-when="' . $json . '" class="adminkit-visibility-wrapper' . $hidden . '">' . $inner . '</div>';
    }
}
