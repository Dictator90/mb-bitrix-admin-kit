<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Performance;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;

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
            static fn(mixed $id): bool => $id !== null && $id !== ''
        ));

        $runByFilter = $ids === [] && $context->filter !== [];
        $action = $context->action;

        if ($ids !== []) {
            return [];
        }

        if (!$runByFilter) {
            return ['No selected ids were provided.'];
        }

        if ($action instanceof BulkAction && !$action->canRunByFilter()) {
            return ['Run by filter is not explicitly allowed for this bulk action.'];
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
}
