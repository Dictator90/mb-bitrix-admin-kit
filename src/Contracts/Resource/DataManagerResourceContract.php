<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

interface DataManagerResourceContract extends
    ResourceContract,
    CrudResourceContract,
    ResourceOrmContract,
    ResourcePersistenceContract
{
}
