<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use Throwable;

class BulkUpdateAction extends BulkAction
{
    /** @var array<string,mixed>|null */
    protected ?array $data = null;

    /** @param array<string,mixed> $data */
    public function update(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function execute(BulkOperationContext $context): BulkResult
    {
        if (!$this->checkCsrf()) {
            return BulkResult::failure('Invalid CSRF token.');
        }

        $ids = $this->selectedIds($context);
        if ($ids === []) {
            return BulkResult::failure('Не выбраны элементы');
        }

        $context = $context->with(['selectedIds' => $ids, 'action' => $this]);
        $result = new BulkResult();

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
                    operation: 'update',
                    item: $item,
                );

                if (!$context->resource->canUpdate($permissionContext)) {
                    $result->addSkipped($id, 'Update permission denied.');
                    continue;
                }

                try {
                    $updateResult = $context->resource->updateItemResult(
                        $id,
                        new FormData($this->data, $this->data, $this->data),
                    );
                    if ($updateResult->isSuccess()) {
                        $result->addSuccess($id);
                    } else {
                        $result->addError($id, $updateResult->errors());
                    }
                } catch (Throwable $exception) {
                    $result->addError($id, $exception->getMessage());
                }
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
