<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;

final class IndexBulkActionHandler
{
    /** @return array{success:bool,message:string}|null */
    public function handle(IndexPage $page, string $actionId): ?array
    {
        if ($actionId === 'export_selected') {
            (new IndexExportHandler())->handle($page, $page->resolveSelectedIds());

            return null;
        }

        if (!$page->hasResource() || !$page->resource() instanceof DataManagerResourceContract) {
            return null;
        }

        $resource = $page->resource();

        foreach ($page->bulkActions() as $bulkAction) {
            if (!$bulkAction instanceof BulkAction || $bulkAction->getId() !== $actionId) {
                continue;
            }

            $action = $bulkAction->isDelete() && !$bulkAction instanceof MassDeleteAction
                ? new MassDeleteAction($bulkAction->getId(), $bulkAction->getLabel())
                : $bulkAction;

            $selectedIds = $page->resolveSelectedIds();
            $grid = $page->buildGrid();
            $gridContext = (new GridDataLoader())->makeContext($resource, $grid, $page->request);
            $queryParams = (new GridQueryBuilder())->build($resource, $gridContext, $page->definition());
            $filter = $selectedIds === [] && $action->canRunByFilter()
                ? (is_array($queryParams['filter'] ?? null) ? $queryParams['filter'] : [])
                : [];

            $context = new BulkOperationContext(
                resource: $resource,
                action: $action,
                selectedIds: $selectedIds,
                userId: $page->currentUserId(),
                request: $page->request,
                filter: $filter,
                gridContext: $gridContext,
            );

            $guardErrors = (new QueryGuard())->validateBulkOperation($context);
            if ($guardErrors !== []) {
                $payload = [
                    'message' => implode(' ', $guardErrors),
                    'success' => false,
                ];
                $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$resource->getGridId()] = $payload;

                return $payload;
            }

            $result = $action->execute($context);
            $payload = [
                'message' => $result->message(),
                'success' => $result->isSuccess(),
            ];
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$resource->getGridId()] = $payload;

            return $payload;
        }

        return null;
    }
}
