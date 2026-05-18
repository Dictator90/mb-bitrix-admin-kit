<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait HasFieldImport
{
    protected bool $importable = true;
    protected bool $system = false;

    public function importable(bool $importable = true): static
    {
        $this->importable = $importable;

        return $this;
    }

    public function system(bool $system = true): static
    {
        $this->system = $system;

        return $this;
    }

    public function isImportable(): bool
    {
        return $this->importable;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }
}
