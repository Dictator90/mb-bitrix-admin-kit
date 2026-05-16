<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourcePagesContract
{
    /** @return iterable<class-string<\MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract>> */
    public function pages(): iterable;
}
