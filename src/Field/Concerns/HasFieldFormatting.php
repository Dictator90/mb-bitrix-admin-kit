<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;

trait HasFieldFormatting
{
    protected ?Closure $formatter = null;
    protected ?Closure $preview = null;
    protected ?Closure $displayCallback = null;

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

    public function displayUsing(Closure $callback): static
    {
        $this->displayCallback = $callback;

        return $this;
    }

    public function displayValue(mixed $value, array $row = [], array $context = []): mixed
    {
        if ($this->displayCallback instanceof Closure) {
            return ($this->displayCallback)($value, $row, $context);
        }

        if ($this->formatter instanceof Closure) {
            return ($this->formatter)($value);
        }

        return $value;
    }

    public function previewValue(mixed $value): string
    {
        if ($this->preview instanceof Closure) {
            return (string)($this->preview)($value);
        }

        return (string)$this->displayValue($value);
    }
}
