<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldFilterContract
{
    public function getFilterType(): ?string;
}
