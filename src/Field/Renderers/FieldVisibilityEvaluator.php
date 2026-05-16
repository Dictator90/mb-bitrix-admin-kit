<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Renderers;

final class FieldVisibilityEvaluator
{
    public function isVisible(array $rule, mixed $currentValue): bool
    {
        $str = (string)($currentValue ?? '');
        if (isset($rule['values'])) {
            return in_array($str, array_map('strval', (array)$rule['values']), true);
        }

        $operator = (string)($rule['operator'] ?? '=');
        $expected = (string)($rule['value'] ?? '');

        return match ($operator) {
            '=', '==', '===' => $str === $expected,
            '!=', '<>', '!==' => $str !== $expected,
            default => $str === $expected,
        };
    }
}
