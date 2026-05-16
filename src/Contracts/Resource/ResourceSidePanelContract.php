<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourceSidePanelContract
{
    public function useSidePanel(): bool;

    public function createInSidePanel(): bool;

    public function editInSidePanel(): bool;

    public function detailInSidePanel(): bool;

    public function sidePanelWidth(): int;

    public function closeSidePanelAfterSave(): bool;
}
