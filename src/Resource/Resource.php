<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Resource\Concerns\HasCrudResourcePages;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceAuthorization;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceFields;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceGrid;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceIdentity;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceLifecycle;
use MB\Bitrix\AdminKit\Resource\Concerns\HasResourceMenu;

/**
 * Base administrative resource: identity, menu, and pages.
 *
 * Core resource class that defines a section in the admin panel.
 * For CRUD functionality, extend {@see CrudResource} or {@see DataManagerResource}.
 *
 */
abstract class Resource implements ResourceContract
{
    use HasResourceIdentity;
    use HasResourceMenu;
    use HasCrudResourcePages;
    use HasResourceFields;
    use HasResourceAuthorization;
    use HasResourceGrid;
    use HasResourceLifecycle;

    public function hasCrud(): bool
    {
        return method_exists($this, 'getDataManagerClass') && $this->getDataManagerClass() !== null;
    }

    public function databaseTableName(): string
    {
        if (!method_exists($this, 'getDataManagerClass')) {
            return '';
        }

        $class = $this->getDataManagerClass();

        return $class && method_exists($class, 'getTableName')
            ? (string)$class::getTableName()
            : '';
    }
}
