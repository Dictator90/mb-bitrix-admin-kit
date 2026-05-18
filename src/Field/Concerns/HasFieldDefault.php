<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait HasFieldDefault
{
    protected mixed $default = null;

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }
}
