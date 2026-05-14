<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

/**
 * Base class for standalone admin pages that are not backed by an ORM DataManager.
 */
abstract class AbstractPage
{
    abstract public static function getId(): string;

    abstract public static function getTitle(): string;

    public static function getSort(): int
    {
        return 100;
    }

    public static function getMenuIcon(): string
    {
        return '';
    }

    public static function isStandalone(): bool
    {
        return true;
    }

    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    public static function getParentMenuId(): ?string
    {
        return null;
    }

    public function id(): string
    {
        return AdminString::slug(static::getId());
    }

    public function title(): string
    {
        return static::getTitle();
    }

    public function sort(): int
    {
        return static::getSort();
    }

    public function icon(): ?string
    {
        $icon = static::getMenuIcon();

        return $icon !== '' ? $icon : null;
    }

    public function group(): ?string
    {
        return static::getParentMenuId();
    }

    public function canView(PermissionContext $context): bool
    {
        return true;
    }

    public function url(array $params = []): string
    {
        return (new UrlGenerator($this->baseUrl()))->pageUrl($this->id(), $params);
    }

    abstract public function render();

    protected function baseUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '';
    }
}
