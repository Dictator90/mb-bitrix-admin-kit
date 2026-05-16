<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface CrudResourceContract extends
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
    ResourceLifecycleContract
{
}
