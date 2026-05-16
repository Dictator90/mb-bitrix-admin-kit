<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldImportContract
{
    public function isImportable(): bool;

    public function importable(bool $importable = true): static;
}
