<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\Page\StandalonePageContract;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

abstract class StandalonePage extends Page implements StandalonePageContract
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

    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    public static function getParentMenuId(): ?string
    {
        return null;
    }

    public static function isStandalone(): bool
    {
        return true;
    }

    public function __construct(array $params = [])
    {
        parent::__construct($params);
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

    /** @param array<string,mixed> $params */
    public function url(array $params = []): string
    {
        return (new UrlGenerator($this->baseUrl()))->pageUrl($this->id(), $params);
    }

    protected function baseUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '';
    }
}
