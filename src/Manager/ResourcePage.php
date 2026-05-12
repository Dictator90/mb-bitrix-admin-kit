<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\Context;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;

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
        $id = (int)($request->get('id') ?: 0);

        match ($action) {
            'add' => $this->resource->formPage()->render(),
            'edit' => $this->resource->formPage($id ?: null)->render(),
            'view' => $this->resource->detailPage($id)->render(),
            default => $this->resource->indexPage()->render(),
        };
    }
}
