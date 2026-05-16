<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface DataManagerResourceContract extends
    CrudResourceContract,
    ResourceOrmContract,
    ResourcePersistenceContract
{
}
