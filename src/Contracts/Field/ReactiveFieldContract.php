<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use Closure;

interface ReactiveFieldContract
{
    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static;
}
