<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceMenu
{
    public static function getSort(): int
    {
        return 100;
    }

    public static function getMenuIcon(): string
    {
        return '';
    }

    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    /**
     * Return the getId() of a parent Resource/Page to nest this item in the menu.
     * Null = root-level item.
     */
    public static function getParentMenuId(): ?string
    {
        return null;
    }

    public function group(): ?string
    {
        return static::getParentMenuId();
    }
}
