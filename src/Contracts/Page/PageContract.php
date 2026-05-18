<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

interface PageContract
{
    public static function pageName(): string;

    public function pageType(): ?PageType;

    public function title(): string;

    public function render(): void;
}
