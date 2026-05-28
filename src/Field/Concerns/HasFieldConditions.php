<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Bitrix\AdminKit\Field\FieldConditionContext;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldConditions
{
    /** @var list<array{condition: Closure, modifier: Closure, dependsOn: list<string>}> */
    protected array $conditionalModifiers = [];

    protected function normalizeCondition(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): ConditionTree|Closure|array
    {
        if ($condition instanceof ConditionTree || $condition instanceof Closure) {
            return $condition;
        }

        if ($value === null && $operator !== null && !in_array($operator, ['=', '==', '===', '!=', '<>', '!==', 'in', 'not in'], true)) {
            $value = $operator;
            $operator = '=';
        }

        return ['column' => $condition, 'operator' => $operator ?? '=', 'value' => $value];
    }

    /** @param string|list<string>|null $dependsOn */
    public function when(Closure $condition, Closure $modifier, string|array|null $dependsOn = null): static
    {
        $depends = $this->normalizeDependsOnColumns($dependsOn);
        $this->conditionalModifiers[] = ['condition' => $condition, 'modifier' => $modifier, 'dependsOn' => $depends];

        if ($depends !== [] && method_exists($this, 'dependsOn')) {
            $this->dependsOn($depends, function (self $field, mixed $value, array $formData) use ($condition, $modifier): void {
                unset($value);
                $ctx = FieldConditionContext::fromArray($formData, PageType::FORM);
                if ($this->callConditionClosure($condition, $ctx, $field)) {
                    $this->callModifierClosure($modifier, $field, $ctx);
                }
            });
        }

        return $this;
    }

    /** @param FieldConditionContext|array<string,mixed> $context */
    public function applyConditionalModifiers(FieldConditionContext|array $context): static
    {
        $ctx = is_array($context) ? FieldConditionContext::fromArray($context, PageType::FORM) : $context;
        foreach ($this->conditionalModifiers as $entry) {
            if ($this->callConditionClosure($entry['condition'], $ctx, $this)) {
                $this->callModifierClosure($entry['modifier'], $this, $ctx);
            }
        }

        return $this;
    }

    protected function conditionToClosure(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): Closure
    {
        $normalized = $this->normalizeCondition($condition, $operator, $value);

        return function (FieldConditionContext $ctx) use ($normalized): bool {
            return $this->evaluateFieldCondition($normalized, $ctx->data);
        };
    }

    protected function evaluateFieldCondition(ConditionTree|Closure|array|bool $condition, array $data): bool
    {
        if (is_bool($condition)) {
            return $condition;
        }

        if ($condition instanceof Closure) {
            return $this->callConditionClosure($condition, FieldConditionContext::fromArray($data, PageType::FORM), $this);
        }

        if ($condition instanceof ConditionTree) {
            return AdminCondition::evaluate($condition, ['form' => $data]) || $this->evaluateConditionTreeFallback($condition, $data);
        }

        return $this->matchesCondition($condition, $data);
    }

    /**
     * @param array<string,mixed> $condition
     * @param array<string,mixed> $data
     */
    protected function matchesCondition(array $condition, array $data): bool
    {
        $actual = $data[$condition['column']] ?? null;
        $expected = $condition['value'] ?? null;

        return match ($condition['operator'] ?? '=') {
            '=', '==', '===' => (string) $actual === (string) $expected,
            '!=', '<>', '!==' => (string) $actual !== (string) $expected,
            'in' => in_array($actual, (array) $expected, true),
            'not in' => !in_array($actual, (array) $expected, true),
            default => false,
        };
    }

    /** @return list<string> */
    /** @param string|list<string>|null $dependsOn
     * @return list<string> */
    protected function normalizeDependsOnColumns(string|array|null $dependsOn): array
    {
        if ($dependsOn === null) {
            return [];
        }

        return array_values(array_filter(array_map('strval', (array) $dependsOn), static fn (string $col): bool => $col !== ''));
    }

    protected function callConditionClosure(Closure $closure, FieldConditionContext $ctx, self $field): bool
    {
        $args = [$ctx, $field, $ctx->data, $ctx->item, $ctx->pageType, $ctx->mode];
        $call = array_slice($args, 0, (new \ReflectionFunction($closure))->getNumberOfParameters());

        return (bool) $closure(...$call);
    }

    protected function callModifierClosure(Closure $closure, self $field, FieldConditionContext $ctx): void
    {
        $args = [$field, $ctx, $ctx->data, $ctx->item, $ctx->pageType, $ctx->mode];
        $call = array_slice($args, 0, (new \ReflectionFunction($closure))->getNumberOfParameters());
        $closure(...$call);
    }

    private function evaluateConditionTreeFallback(ConditionTree $tree, array $data): bool
    {
        if (!method_exists($tree, 'getConditions')) {
            return false;
        }

        $results = [];
        foreach ($tree->getConditions() as $condition) {
            if ($condition instanceof ConditionTree) {
                $results[] = $this->evaluateConditionTreeFallback($condition, $data);
                continue;
            }
            if (!method_exists($condition, 'getTarget') || !method_exists($condition, 'getOperator') || !method_exists($condition, 'getValue')) {
                continue;
            }
            $target = $condition->getTarget();
            $actual = is_string($target) ? ($data[$target] ?? null) : $target;
            $results[] = $this->matchesCondition(['column' => '__inline__', 'operator' => $condition->getOperator(), 'value' => $condition->getValue()], ['__inline__' => $actual]);
        }

        return $results !== [] && !in_array(false, $results, true);
    }
}
