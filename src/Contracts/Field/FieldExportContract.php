<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldExportContract
{
    public function isExportable(): bool;

    public function exportable(bool $exportable = true): static;
}
