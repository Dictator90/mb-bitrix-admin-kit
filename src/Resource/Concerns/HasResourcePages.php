<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourcePages
{
    /** @return iterable<class-string<\MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract>> */
    public function pages(): iterable
    {
        return [];
    }
}
