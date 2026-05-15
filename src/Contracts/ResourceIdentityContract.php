<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface ResourceIdentityContract
{
    public static function getId(): string;

    public function getTitle(): string;
}
