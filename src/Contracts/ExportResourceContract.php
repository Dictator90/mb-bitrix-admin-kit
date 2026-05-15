<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

/**
 * Narrow contract for CSV export flows.
 */
interface ExportResourceContract extends
    ResourceIdentityContract,
    ExportableResourceContract,
    ResourcePermissionContract,
    OrmResourceContract,
    IndexResourceContract
{
}
