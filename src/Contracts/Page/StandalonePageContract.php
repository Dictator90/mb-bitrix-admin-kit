<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

use MB\Bitrix\AdminKit\Security\PermissionContext;

interface StandalonePageContract extends PageContract
{
    public static function getId(): string;

    public static function getTitle(): string;

    public static function getSort(): int;

    public static function getMenuIcon(): string;

    public static function isVisibleInMenu(): bool;

    public static function getParentMenuId(): ?string;

    public function id(): string;

    /** @param array<string,mixed> $params */
    public function url(array $params = []): string;

    public function canView(PermissionContext $context): bool;
}
