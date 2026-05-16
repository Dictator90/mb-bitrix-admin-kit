<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Contracts\Resource\ResourceIdentityContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceMenuContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePagesContract;

/**
 * Core aggregate resource contract: identity, menu, and page registration.
 *
 * For CRUD DSL use {@see \MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract}.
 * For Bitrix D7 ORM persistence use {@see \MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract}.
 */
interface ResourceContract extends
    ResourceIdentityContract,
    ResourceMenuContract,
    ResourcePagesContract
{
}
