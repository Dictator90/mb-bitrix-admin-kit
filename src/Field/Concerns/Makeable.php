<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait Makeable
{
    public static function make(?string $label = null, ?string $column = null): static
    {
        return new static($label, $column);
    }
}
