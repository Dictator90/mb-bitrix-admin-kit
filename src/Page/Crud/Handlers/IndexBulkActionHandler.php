<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
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
        $bulkAction = $this->findBulkActionById($page->bulkActions(), $actionId);

        if ($bulkAction === null) {
            return null;
        }

        $action = $bulkAction;

        $forAll = $page->isForAllRowsSelected();
        $selectedIds = $forAll ? [] : $page->resolveSelectedIds();
        $grid = $page->buildGrid();
        $gridContext = (new GridDataLoader())->makeContext($resource, $grid, $page->request);
        $queryParams = (new GridQueryBuilder())->build($resource, $gridContext, $page->definition());
        $filter = $forAll && $action->canRunByFilter()
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
            forAll: $forAll,
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

    /**
     * @param iterable<BulkPanelItemContract> $items
     * @param string $id
     * @return BulkAction|null
     */
    private function findBulkActionById(iterable $items, string $id): ?BulkAction
    {
        foreach ($items as $item) {
            if ($item instanceof BulkAction && $item->getId() === $id) {
                return $item;
            }

            if ($item instanceof BulkActionDropdown) {
                foreach ($item->getItems() as $child) {
                    if ($child->getId() === $id) {
                        return $child;
                    }
                }
            }
        }

        return null;
    }
}
