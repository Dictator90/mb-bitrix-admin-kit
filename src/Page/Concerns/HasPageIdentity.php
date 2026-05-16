<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

trait HasPageIdentity
{
    protected string $title = '';

    protected ?PageType $pageType = null;

    public static function pageName(): string
    {
        return 'page';
    }

    public function pageType(): ?PageType
    {
        return $this->pageType;
    }

    public function title(): string
    {
        return $this->title !== '' ? $this->title : static::pageName();
    }
}
