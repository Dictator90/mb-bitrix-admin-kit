<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

interface PageTypeAwareContract
{
    public function withPageType(PageType $type): static;
}
