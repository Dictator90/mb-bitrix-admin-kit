<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldDefaultContract
{
    public function getDefault(): mixed;

    public function default(mixed $value): static;
}
