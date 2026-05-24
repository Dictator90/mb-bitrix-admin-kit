<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Bitrix\AdminKit\Field\FieldConditionContext;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldReadonly
{
    protected bool $readonly = false;
    /** @var array<int, Closure|ConditionTree|array<string,mixed>> */
    protected array $readonlyWhen = [];

    /** @param string|list<string>|null $dependsOn */
    public function readonly(bool|Closure $readonly = true, string|array|null $dependsOn = null): static
    {
        if (is_bool($readonly)) {
            $this->readonly = $readonly;
            return $this;
        }
        $this->readonlyWhen[] = $readonly;
        if ($dependsOn !== null) {
            $this->when($readonly, static function (self $field): void {
                $field->readonly(true);
            }, $dependsOn);
        }
        return $this;
    }

    public function readonlyWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null, bool $reactive = false): static
    {
        $normalized = $this->normalizeCondition($condition, $operator, $value);
        $this->readonlyWhen[] = $normalized;
        if ($reactive && is_array($normalized)) {
            $this->readonly($this->conditionToClosure($condition, $operator, $value), $normalized['column']);
        }
        return $this;
    }

    public function readonlyOnUpdate(bool $readonly = true): static
    {
        if ($readonly) {
            $this->readonly(static fn (FieldConditionContext $ctx): bool => $ctx->isEdit());
        }
        return $this;
    }

    public function readonlyOnCreate(bool $readonly = true): static
    {
        if ($readonly) {
            $this->readonly(static fn (FieldConditionContext $ctx): bool => $ctx->isCreate());
        }
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
