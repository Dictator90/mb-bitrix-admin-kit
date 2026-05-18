<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Concerns;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

trait HasComponentContext
{
    protected ?DataWrapper $item = null;
    protected PageType $pageType = PageType::FORM;

    public function withItem(?DataWrapper $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function withPageType(PageType $type): static
    {
        $this->pageType = $type;

        return $this;
    }

    public function context(): ComponentContext
    {
        return new ComponentContext(
            item: $this->item,
            pageType: $this->pageType,
        );
    }
}
