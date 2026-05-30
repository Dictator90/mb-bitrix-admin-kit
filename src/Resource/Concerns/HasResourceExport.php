<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceExport
{
    /** Главный выключатель экспорта. По умолчанию выключен — включайте явно в ресурсе. */
    public function exportEnabled(): bool
    {
        return false;
    }

    public function allowExportByFilter(): bool
    {
        return true;
    }

    public function allowExportAll(): bool
    {
        return false;
    }

    public function maxExportRows(): int
    {
        return 5000;
    }
}
