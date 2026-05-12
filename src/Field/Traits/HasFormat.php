<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Traits;

use Closure;

trait HasFormat
{
    protected ?Closure $formatter = null;

    protected ?Closure $preview = null;

    public function format(Closure $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function preview(Closure $preview): static
    {
        $this->preview = $preview;

        return $this;
    }

    public function formatValue(mixed $value): mixed
    {
        if ($this->formatter) {
            return ($this->formatter)($value);
        }

        return $value;
    }

    public function previewValue(mixed $value): string
    {
        if ($this->preview) {
            return (string)($this->preview)($value);
        }

        return (string)$this->formatValue($value);
    }
}
