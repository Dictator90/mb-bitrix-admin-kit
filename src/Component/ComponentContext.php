<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class ComponentContext
{
    /** @param array<string,mixed> $meta */
    public function __construct(
        public readonly ?DataWrapper $item = null,
        public readonly PageType $pageType = PageType::FORM,
        public readonly array $meta = [],
    ) {
    }
}
