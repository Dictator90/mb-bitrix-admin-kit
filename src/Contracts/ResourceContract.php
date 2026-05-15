<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;

/**
 * Aggregate resource contract for backward compatibility.
 *
 * New internal code should depend on narrower contracts such as
 * {@see IndexResourceContract}, {@see FormResourceContract}, or {@see ExportResourceContract}.
 */
interface ResourceContract extends
    ResourceIdentityContract,
    ResourceMenuContract,
    ResourcePermissionContract,
    OrmResourceContract,
    IndexResourceContract,
    FormResourceContract,
    DetailResourceContract,
    ExportableResourceContract,
    ExportResourceContract
{
    /** @return iterable<AsyncAction> */
    public function asyncActions(): iterable;

    /** @return iterable<class-string<PageContract>> */
    public function pages(): iterable;

    public function indexPage(): IndexPage;

    public function formPage(mixed $id = null): FormPage;

    public function detailPage(mixed $id): DetailPage;
}
