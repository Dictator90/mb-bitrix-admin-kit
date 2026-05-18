<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourceIdentityContract
{
    /**
     * Unique slug used in `?page=` routing and admin menu URLs.
     */
    public static function getId(): string;

    /**
     * Human-readable title of the resource.
     */
    public function getTitle(): string;
}
