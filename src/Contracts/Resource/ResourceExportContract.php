<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourceExportContract
{
    public function allowExportByFilter(): bool;

    public function allowExportAll(): bool;

    public function maxExportRows(): int;
}
