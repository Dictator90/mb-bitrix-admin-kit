<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use Closure;

interface ComputedFieldContract
{
    public function computed(Closure $callback): static;
}
