<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use Throwable;

class MassDeleteAction extends BulkAction
{
    public function __construct(string $id = 'delete', ?string $label = null)
    {
        parent::__construct($id, $label ?? LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_DELETE_SELECTED', 'Delete selected'));
        $this
            ->confirm(LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_DELETE_CONFIRM', 'Are you sure you want to delete selected records?'))
            ->danger()
            ->group('danger', LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_DELETE_GROUP', 'Deletion'), 900)
            ->icon('ui-btn-icon-remove')
            ->sort(100);
    }

    public static function make(string $id = 'delete', ?string $label = null): static
    {
        return new static($id, $label);
    }

    public function execute(BulkOperationContext $context): BulkResult
    {
        if (!$this->checkCsrf()) {
            return BulkResult::failure('Invalid CSRF token.');
        }

        $guardErrors = (new QueryGuard())->validateBulkOperation($context);
        if ($guardErrors !== []) {
            return BulkResult::failure(implode(' ', $guardErrors));
        }

        $ids = $this->selectedIds($context);
        if ($ids === []) {
            return BulkResult::failure(LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_EMPTY_SELECTION', 'No items selected.'));
        }

        $context = $context->with(['selectedIds' => $ids, 'action' => $this]);
        $result = new BulkResult();

        $operationContext = new DbOperationContext(
            resource: $context->resource,
            operation: 'massDelete',
            userId: $context->userId,
            request: $context->request,
        );

        if (method_exists($context->resource, 'beforeMassDelete')) {
            try {
                $context->resource->beforeMassDelete($ids, $operationContext);
            } catch (Throwable $exception) {
                return BulkResult::failure($exception->getMessage());
            }
        }

        $chunkSize = $this->chunkSize($context);

        foreach ($this->chunks($ids, $chunkSize) as $chunk) {
            foreach ($chunk as $id) {
                $item = $context->resource->findItem($id);
                if ($item === null) {
                    $result->addError($id, 'Item was not found.');
                    continue;
                }

                if (!$this->isRunnable($context, $item)) {
                    $result->addSkipped($id, 'Bulk action is not allowed for this item.');
                    continue;
                }

                $permissionContext = new PermissionContext(
                    userId: $context->userId,
                    resource: $context->resource,
                    operation: 'delete',
                    item: $item,
                );

                if (!$context->resource->canDelete($permissionContext)) {
                    $result->addSkipped($id, 'Delete permission denied.');
                    continue;
                }

                try {
                    $deleteResult = $context->resource->deleteItemResult($id);
                    if ($deleteResult->isSuccess()) {
                        $result->addSuccess($id);
                    } else {
                        $result->addError($id, $deleteResult->errors());
                    }
                } catch (Throwable $exception) {
                    $result->addError($id, $exception->getMessage());
                }
            }
        }

        if (method_exists($context->resource, 'afterMassDelete')) {
            try {
                $context->resource->afterMassDelete($ids, $operationContext);
            } catch (Throwable $exception) {
                $result->addError('_bulk', $exception->getMessage());
            }
        }

        return $result;
    }

    private function chunkSize(BulkOperationContext $context): int
    {
        if (method_exists($context->resource, 'bulkChunkSize')) {
            return (int)$context->resource->bulkChunkSize();
        }

        return 100;
    }

    /**
     * @param array<int,mixed> $ids
     * @return array<int,array<int,mixed>>
     */
    private function chunks(array $ids, int $chunkSize): array
    {
        $chunkSize = max(1, $chunkSize);

        return array_chunk(AdminCollection::make($ids)->all(), $chunkSize);
    }

}
