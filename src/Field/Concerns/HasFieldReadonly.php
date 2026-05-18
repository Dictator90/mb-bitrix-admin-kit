<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldReadonly
{
    protected bool $readonly = false;

    /** @var array<int, Closure|ConditionTree|array<string,mixed>> */
    protected array $readonlyWhen = [];

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function readonlyWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static
    {
        $this->readonlyWhen[] = $this->normalizeCondition($condition, $operator, $value);

        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readonly;
    }

    public function isReadOnlyFor(array $data = []): bool
    {
        if ($this->readonly) {
            return true;
        }

        foreach ($this->readonlyWhen as $condition) {
            if ($this->evaluateFieldCondition($condition, $data)) {
                return true;
            }
        }

        return false;
    }
}
