<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourceExportContract
{
    /** Главный выключатель экспорта (тулбар, групповые действия, эндпоинты). По умолчанию выключен. */
    public function exportEnabled(): bool;

    public function allowExportByFilter(): bool;

    public function allowExportAll(): bool;

    public function maxExportRows(): int;
}
