<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;

trait HasComputedValue
{
    protected ?Closure $computedCallback = null;

    public function computed(Closure $callback): static
    {
        $this->computedCallback = $callback;
        $this->sortable(false);

        return $this;
    }

    public function isComputed(): bool
    {
        return $this->computedCallback instanceof Closure;
    }

    /** @param array<string,mixed> $row */
    public function computeValue(array $row): mixed
    {
        if (!$this->computedCallback instanceof Closure) {
            return $row[$this->column] ?? null;
        }

        return ($this->computedCallback)($row);
    }
}
