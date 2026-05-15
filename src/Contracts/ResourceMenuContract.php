<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface ResourceMenuContract
{
    public static function getSort(): int;

    public static function getMenuIcon(): string;

    public static function isVisibleInMenu(): bool;

    public static function getParentMenuId(): ?string;
}
