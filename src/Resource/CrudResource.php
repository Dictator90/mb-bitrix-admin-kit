<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceActions;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceAuthorization;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceExport;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceFields;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceFilters;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceGrid;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceGrouping;
use MB\Bitrix\AdminKit\Resource\Concerns\HasCrudResourcePages;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceLifecycle;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceQuery;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceSidePanel;

/**
 * Base class for CRUD-enabled resources.
 *
 * Defines the CRUD DSL: fields, filters, actions, grid settings, and authorization.
 * Does not include persistence logic by default. For Bitrix D7 ORM persistence,
 * extend {@see DataManagerResource}.
 *
 * @template T of DataManager
 * @extends Resource<T>
 */
abstract class CrudResource extends Resource implements CrudResourceContract
{
    use HasCrudResourcePages;
    use HasResourceFields;
    use HasResourceFilters;
    use HasResourceActions;
    use HasResourceAuthorization;
    use HasResourceSidePanel;
    use HasResourceGrid;
    use HasResourceQuery;
    use HasResourceGrouping;
    use HasResourceExport;
    use HasResourceLifecycle;

    public function hasCrud(): bool
    {
        return false;
    }
}
