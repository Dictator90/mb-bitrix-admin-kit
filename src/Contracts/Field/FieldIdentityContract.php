<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldIdentityContract
{
    public function getColumn(): string;

    public function getLabel(): string;
}
