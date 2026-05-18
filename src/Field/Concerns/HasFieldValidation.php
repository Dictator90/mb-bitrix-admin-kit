<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use MB\Bitrix\AdminKit\Support\Validation\Rules;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldValidation
{
    protected bool $required = false;

    /** @var Closure[] */
    protected array $validators = [];

    /** @var array<int, bool|Closure|ConditionTree|array<string,mixed>> */
    protected array $requiredWhen = [];

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function requiredWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static
    {
        $this->requiredWhen[] = $this->normalizeCondition($condition, $operator, $value);

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function validate(mixed $value): array|static
    {
        if ($value instanceof Closure) {
            $this->validators[] = $value;

            return $this;
        }

        return $this->runValidation($value);
    }

    public function minLength(int $min, string $message = ''): static
    {
        return $this->validate(Rules::minLength($min, $message));
    }

    public function maxLength(int $max, string $message = ''): static
    {
        return $this->validate(Rules::maxLength($max, $message));
    }

    public function email(string $message = ''): static
    {
        return $this->validate(Rules::email($message));
    }

    public function url(string $message = ''): static
    {
        return $this->validate(Rules::url($message));
    }

    public function numeric(string $message = ''): static
    {
        return $this->validate(Rules::numeric($message));
    }

    public function min(float|int $min, string $message = ''): static
    {
        return $this->validate(Rules::min($min, $message));
    }

    public function max(float|int $max, string $message = ''): static
    {
        return $this->validate(Rules::max($max, $message));
    }

    public function pattern(string $regex, string $message = ''): static
    {
        return $this->validate(Rules::pattern($regex, $message));
    }

    public function in(array $allowed, string $message = ''): static
    {
        return $this->validate(Rules::in($allowed, $message));
    }

    /** @return string[] */
    public function runValidation(mixed $value, array $data = []): array
    {
        $errors = AdminCollection::make([])->all();
        $required = $this->required || $this->hasActiveRequiredCondition($data);

        if ($required && $this->isEmptyValidationValue($value)) {
            $errors[] = LocalizedMessage::get(
                __FILE__,
                'MB_ADMIN_KIT_FIELD_REQUIRED',
                'Field "#FIELD#" is required.',
                ['#FIELD#' => $this->getLabel()],
            );
        }

        foreach ($this->validators as $validator) {
            $result = $validator($value, $data);
            if ($result === false) {
                $errors[] = LocalizedMessage::get(
                    __FILE__,
                    'MB_ADMIN_KIT_FIELD_INVALID',
                    'Field "#FIELD#" contains an invalid value.',
                    ['#FIELD#' => $this->getLabel()],
                );
            } elseif (is_string($result) && $result !== '') {
                $errors[] = $result;
            }
        }

        return $errors;
    }

    protected function normalizeCondition(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): ConditionTree|Closure|array
    {
        if ($condition instanceof ConditionTree || $condition instanceof Closure) {
            return $condition;
        }

        return [
            'column' => $condition,
            'operator' => $operator ?? '=',
            'value' => $value,
        ];
    }

    protected function evaluateFieldCondition(ConditionTree|Closure|array|bool $condition, array $data): bool
    {
        if (is_bool($condition)) {
            return $condition;
        }

        if ($condition instanceof Closure) {
            return (bool)$condition($data);
        }

        if ($condition instanceof ConditionTree) {
            return AdminCondition::evaluate($condition, ['form' => $data]) || $this->evaluateConditionTreeFallback($condition, $data);
        }

        $actual = $data[$condition['column']] ?? null;
        $expected = $condition['value'] ?? null;

        return match ($condition['operator'] ?? '=') {
            '=', '==', '===' => (string)$actual === (string)$expected,
            '!=', '<>', '!==' => (string)$actual !== (string)$expected,
            'in' => in_array($actual, (array)$expected, true),
            'not in' => !in_array($actual, (array)$expected, true),
            default => false,
        };
    }

    private function isEmptyValidationValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && AdminCollection::make($value)->all() === []);
    }

    private function hasActiveRequiredCondition(array $data): bool
    {
        foreach ($this->requiredWhen as $condition) {
            if ($this->evaluateFieldCondition($condition, $data)) {
                return true;
            }
        }

        return false;
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
            $expected = $condition->getValue();
            $operator = $condition->getOperator();

            $results[] = match ($operator) {
                '=', '==', '===' => (string)$actual === (string)$expected,
                '!=', '<>', '!==' => (string)$actual !== (string)$expected,
                'in' => in_array($actual, (array)$expected, true),
                'not in' => !in_array($actual, (array)$expected, true),
                default => false,
            };
        }

        return $results !== [] && !in_array(false, $results, true);
    }

}
