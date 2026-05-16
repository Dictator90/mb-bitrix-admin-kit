<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceIdentity;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceMenu;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourcePages;

/**
 * Base administrative resource: identity, menu, and pages.
 *
 * Core resource class that defines a section in the admin panel.
 * For CRUD functionality, extend {@see CrudResource} or {@see DataManagerResource}.
 *
 * @template T of DataManager
 */
abstract class Resource implements ResourceContract
{
    use HasResourceIdentity;
    use HasResourceMenu;
    use HasResourcePages;
}
