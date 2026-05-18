<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Support\DataWrapper;

interface ResourcePersistenceContract
{
    /** @return array<string,mixed>|null */
    public function findItem(mixed $id): ?array;

    /**
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function getList(array $params = []): array;

    /** @param array<string,mixed> $filter */
    public function getCount(array $filter = []): int;

    /** @param array<string,mixed> $data */
    public function createItem(array $data): mixed;

    /** @param FormData|array<string,mixed> $data */
    public function createItemResult(FormData|array $data, ?DbOperationContext $context = null): DbResult;

    /** @param array<string,mixed> $data */
    public function updateItem(mixed $id, array $data): bool;

    /** @param FormData|array<string,mixed> $data */
    public function updateItemResult(mixed $id, FormData|array $data, ?DbOperationContext $context = null): DbResult;

    public function deleteItem(mixed $id): bool;

    public function deleteItemResult(mixed $id, ?DbOperationContext $context = null): DbResult;

    /** @param array<int,mixed> $ids */
    public function massDelete(array $ids): void;

    public function save(DataWrapper $item): DataWrapper;

    public function delete(int|string $id): bool;

    public function useTransactions(): bool;
}
