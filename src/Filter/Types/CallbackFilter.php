<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use Closure;
use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;

class CallbackFilter extends Filter
{
    protected ?Closure $callback = null;
    protected string $type = 'string';

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function apply(mixed $filter, mixed $value = null): mixed
    {
        if ($filter instanceof Closure) {
            $this->callback = $filter;

            return $this;
        }

        return is_array($filter) ? parent::apply($filter, $value) : $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        if (!$this->callback instanceof Closure || !$context instanceof GridContext) {
            return $filter;
        }

        return ($this->callback)($filter, $value, $context);
    }
}
