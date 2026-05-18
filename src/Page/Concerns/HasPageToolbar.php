<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

trait HasPageToolbar
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    protected function toolbarActions(): iterable
    {
        return [];
    }

    protected function renderToolbar(): void
    {
    }
}
