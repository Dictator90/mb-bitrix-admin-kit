<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Performance;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use Throwable;

final class QueryGuard
{
    /** @return array<string,mixed> */
    public function guardGridParams(array $params, GridContext $context): array
    {
        $maxPageSize = method_exists($context->resource, 'maxPageSize') ? (int)$context->resource->maxPageSize() : 200;
        $limit = (int)($params['limit'] ?? $context->limit);

        if ($limit <= 0) {
            $params['limit'] = max(1, min($context->pageSize, $maxPageSize));
        } elseif ($limit > $maxPageSize) {
            $params['limit'] = $maxPageSize;
        }

        return $params;
    }

    /** @return array<int,string> */
    public function validateBulkOperation(BulkOperationContext $context): array
    {
        $ids = array_values(array_filter(
            AdminCollection::make($context->selectedIds)->all(),
            static fn (mixed $id): bool => $id !== null && $id !== ''
        ));

        $action = $context->action;

        if ($ids !== [] && !$context->forAll) {
            return [];
        }

        if (!$context->forAll) {
            return [];
        }

        if (!$action instanceof BulkAction || !$action->canRunByFilter()) {
            return ['Run by filter is not explicitly allowed for this bulk action.'];
        }

        if ($context->filter === [] && !$action->canRunWithoutFilter()) {
            return ['Running bulk action for all records without filter is not allowed.'];
        }

        $count = $this->bulkRowsCount($context);
        if ($count === null) {
            return ['Bulk operation row count cannot be determined.'];
        }

        $maxBulkRows = method_exists($context->resource, 'maxBulkRows')
            ? (int)$context->resource->maxBulkRows()
            : 5000;
        $maxBulkRows = max(1, $maxBulkRows);

        if ($count > $maxBulkRows) {
            return [sprintf('Bulk operation affects too many rows: %d. Maximum allowed: %d.', $count, $maxBulkRows)];
        }

        return [];
    }

    public function ensureBulkOperationAllowed(BulkOperationContext $context): void
    {
        $errors = $this->validateBulkOperation($context);
        if ($errors !== []) {
            throw new \RuntimeException(implode(' ', $errors));
        }
    }

    private function bulkRowsCount(BulkOperationContext $context): ?int
    {
        if (!method_exists($context->resource, 'getCount')) {
            return null;
        }

        try {
            return (int)$context->resource->getCount($context->filter);
        } catch (Throwable) {
            return null;
        }
    }
}
