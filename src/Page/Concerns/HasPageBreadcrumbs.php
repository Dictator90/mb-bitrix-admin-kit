<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

trait HasPageBreadcrumbs
{
    /** @return array<int,array{title:string,url?:string}> */
    protected function breadcrumbs(): array
    {
        return [];
    }
}
