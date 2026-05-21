<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use MB\Bitrix\AdminKit\Support\ResponseTerminator;

final class IndexExportHandler
{
    /** @param array<int,mixed>|null $selectedIdsOverride */
    public function handle(IndexPage $page, ?array $selectedIdsOverride = null): void
    {
        if (!$page->hasResource() || !$page->resource() instanceof DataManagerResourceContract) {
            return;
        }

        $resource = $page->resource();
        $grid = $page->buildGrid();
        $gridContext = (new GridDataLoader())->makeContext($resource, $grid, $page->request);
        $selectedIds = $selectedIdsOverride ?? [];
        $queryParams = (new GridQueryBuilder())->build($resource, $gridContext, $page->definition());
        $filter = $selectedIds === [] ? (is_array($queryParams['filter'] ?? null) ? $queryParams['filter'] : []) : [];

        $result = ExportAction::make()->execute(new ExportContext(
            resource: $resource,
            selectedIds: $selectedIds,
            filter: $filter,
            userId: $page->currentUserId(),
            format: 'csv',
            gridContext: $gridContext,
        ));

        if (!$result->isSuccess()) {
            $message = trim(implode(' ', array_filter(
                $result->errors,
                static fn (mixed $error): bool => is_string($error) && trim($error) !== '',
            )));
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$resource->getGridId()] = [
                'message' => $message !== '' ? $message : LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_INDEX_EXPORT_ERROR_FALLBACK', 'Export error.'),
                'success' => false,
            ];
            $page->redirect($page->baseListUrl());

            return;
        }

        $page->clearOutputBuffers();
        header('Content-Type: ' . $result->contentType);
        header('Content-Disposition: attachment; filename="' . addslashes($result->filename) . '"');
        echo $result->content;
        ResponseTerminator::terminate();
    }

}
