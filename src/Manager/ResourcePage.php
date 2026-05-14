<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\Context;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;

/**
 * Routes the current HTTP request to the appropriate Resource page:
 *
 *   ?action=add          → FormPage (create)
 *   ?action=edit&id=N    → FormPage (edit)
 *   ?action=view&id=N    → DetailPage
 *   everything else      → IndexPage  (handles delete/delete_bulk/export internally)
 */
final class ResourcePage
{
    public function __construct(private ResourceContract $resource)
    {
    }

    public function render(): void
    {
        $request = Context::getCurrent()->getRequest();
        $action = (string)($request->get('action') ?: $request->getPost('action') ?: 'list');
        $pageName = (string)($request->get('admin_page') ?: $request->getPost('admin_page') ?: '');
        $mode = (string)($request->get('mode') ?: $request->getPost('mode') ?: '');
        $id = $request->get('id') ?: null;

        if ($pageName === '') {
            $pageName = match ($action) {
                'add', 'edit' => 'form',
                'view', 'detail' => 'detail',
                default => 'index',
            };
        }

        if ($mode === '') {
            $mode = $action === 'add' ? 'create' : ($action === 'edit' || $id !== null ? 'edit' : 'create');
        }

        (new ResourcePageResolver())->resolve($this->resource, $pageName, $id, [
            'mode' => $mode,
            'action' => $action,
        ])->render();
    }
}
