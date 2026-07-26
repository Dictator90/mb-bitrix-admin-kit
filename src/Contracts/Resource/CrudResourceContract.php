<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Page\Pages;

interface CrudResourceContract extends
    ResourceContract,
    ResourceIdentityContract,
    ResourceMenuContract,
    ResourcePagesContract,
    ResourceFieldsContract,
    ResourceFiltersContract,
    ResourceActionsContract,
    ResourceAuthorizationContract,
    ResourceSidePanelContract,
    ResourceGridContract,
    ResourceQueryContract,
    ResourceGroupingContract,
    ResourceExportContract,
    ResourceToolbarContract,
    ResourceLifecycleContract
{
    public function indexPage(): IndexPage;

    public function formPage(mixed $id = null): FormPage;

    public function detailPage(mixed $id): DetailPage;

    public function getPages(): Pages;
}
