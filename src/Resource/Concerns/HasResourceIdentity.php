<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Support\AdminString;

trait HasResourceIdentity
{
    protected string $title = '';

    /**
     * Unique slug used in `?page=` routing and admin menu URLs.
     * Defaults to lower-cased class short-name without "Resource" suffix.
     */
    public static function getId(): string
    {
        return AdminString::resourceId(static::class);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
