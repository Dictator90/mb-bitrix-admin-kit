<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceSidePanel
{
    public function useSidePanel(): bool
    {
        return false;
    }

    public function createInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function editInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function detailInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function sidePanelWidth(): int
    {
        return 1100;
    }

    /** Close the slider after a successful save in IFRAME mode (async or full POST). */
    public function closeSidePanelAfterSave(): bool
    {
        return true;
    }
}
