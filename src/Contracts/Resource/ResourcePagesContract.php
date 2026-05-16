<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;

interface ResourcePagesContract
{
    /** @return iterable<class-string<\MB\Bitrix\AdminKit\Contracts\PageContract>> */
    public function pages(): iterable;

    public function indexPage(): IndexPage;

    public function formPage(mixed $id = null): FormPage;

    public function detailPage(mixed $id): DetailPage;
}
