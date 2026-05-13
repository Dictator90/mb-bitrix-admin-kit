<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Support\Conditionable\ConditionTree;

final class AdminCondition
{
    /** @param array<string,mixed> $contexts */
    public static function tree(array $contexts = []): ConditionTree
    {
        return ConditionTree::create($contexts);
    }

    /** @param array<string,mixed> $context */
    public static function evaluate(bool|callable|ConditionTree|null $condition, array $context = []): bool
    {
        if ($condition === null) {
            return true;
        }

        if (is_bool($condition)) {
            return $condition;
        }

        if ($condition instanceof ConditionTree) {
            foreach ($context as $alias => $value) {
                $condition->context($value, is_string($alias) ? $alias : null);
            }
            return $condition->calculate()->result();
        }

        return (bool) $condition($context);
    }

    /** @param array<string,mixed> $context */
    public function __invoke(bool|callable|ConditionTree|null $condition, array $context = []): bool
    {
        return self::evaluate($condition, $context);
    }
}
