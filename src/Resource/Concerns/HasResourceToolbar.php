<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceToolbar
{
    public function toolbarTitle(): ?string
    {
        return null;
    }

    public function toolbarEditableTitle(): bool
    {
        return false;
    }

    public function toolbarFavoriteStar(): bool
    {
        return false;
    }

    /** @return array<string,string>|null */
    public function toolbarCopyLink(): ?array
    {
        return null;
    }

    public function toolbarBeforeTitleHtml(): ?string
    {
        return null;
    }

    public function toolbarAfterTitleHtml(): ?string
    {
        return null;
    }

    public function toolbarUnderTitleHtml(): ?string
    {
        return null;
    }

    public function toolbarRightHtml(): ?string
    {
        return null;
    }
}
