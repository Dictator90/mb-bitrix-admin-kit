<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;

final class IndexDeleteHandler
{
    public function handle(IndexPage $page): bool
    {
        if (!$page->hasResource() || !$page->resource() instanceof ResourcePersistenceContract) {
            return false;
        }

        $resource = $page->resource();
        $id = GridRowId::normalizeItemId($page->request->get('id'));
        if ($id === null || $id === '') {
            return false;
        }

        $item = $resource->findItem($id);
        if ($item === null) {
            return false;
        }

        if (!$resource->canDelete(new PermissionContext(resource: $resource, operation: 'delete', item: $item))) {
            return false;
        }

        $resource->delete($id);

        return true;
    }
}
