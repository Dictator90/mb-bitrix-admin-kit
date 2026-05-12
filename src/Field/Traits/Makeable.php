<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Traits;

trait Makeable
{
    public static function make(string $label, ?string $column = null): static
    {
        return new static($label, $column);
    }
}
