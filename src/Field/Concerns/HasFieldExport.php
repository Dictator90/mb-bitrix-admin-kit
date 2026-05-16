<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait HasFieldExport
{
    protected bool $exportable = true;
    protected bool $private = false;

    public function exportable(bool $exportable = true): static
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function private(bool $private = true): static
    {
        $this->private = $private;

        return $this;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }
}
