<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Support\DataWrapper;

interface FormResourceContract
{
    /** @return iterable<FieldContract> */
    public function formFields(): iterable;

    /** @return iterable<Tab> */
    public function formTabs(): iterable;

    /**
     * @param array<string,mixed> $data
     */
    public function createItem(array $data): mixed;

    /** @param FormData|array<string,mixed> $data */
    public function createItemResult(FormData|array $data, ?DbOperationContext $context = null): DbResult;

    /**
     * @param array<string,mixed> $data
     */
    public function updateItem(mixed $id, array $data): bool;

    /** @param FormData|array<string,mixed> $data */
    public function updateItemResult(mixed $id, FormData|array $data, ?DbOperationContext $context = null): DbResult;

    public function save(DataWrapper $item): DataWrapper;

    public function delete(int|string $id): bool;

    public function deleteItem(mixed $id): bool;

    public function deleteItemResult(mixed $id, ?DbOperationContext $context = null): DbResult;

    /**
     * @param array<int,mixed> $ids
     */
    public function massDelete(array $ids): void;

    public function useTransactions(): bool;

    public function beforeValidate(FormData $data, DbOperationContext $context): void;

    public function afterValidate(FormData $data, DbOperationContext $context): void;
}
