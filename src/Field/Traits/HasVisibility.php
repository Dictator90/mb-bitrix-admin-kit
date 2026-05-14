<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Traits;

use Closure;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

trait HasVisibility
{
    protected Closure|bool $visible = true;

    /** @var PageType[] */
    protected array $hiddenOn = [];

    public function visible(Closure|bool $condition): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function hideOn(PageType ...$pageTypes): static
    {
        $this->hiddenOn = array_merge($this->hiddenOn, $pageTypes);

        return $this;
    }

    public function showOn(PageType ...$pageTypes): static
    {
        $allPages = PageType::cases();
        $this->hiddenOn = array_filter(
            $allPages,
            fn (PageType $pt) => !in_array($pt, $pageTypes, true),
        );

        return $this;
    }

    public function isVisibleOn(PageType $pageType): bool
    {
        if (in_array($pageType, $this->hiddenOn, true)) {
            return false;
        }

        if ($this->visible instanceof Closure) {
            return (bool)($this->visible)();
        }

        return $this->visible;
    }
}
