<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Tab;

interface ResourceContract
{
    public function getTitle(): string;

    public function getDataManagerClass(): ?string;

    public function dataManagerClass(): string;

    public function getPrimaryKey(): string;

    public function primaryKey(): string;

    public function hasCrud(): bool;

    public function getGridId(): string;

    public function getFilterId(): string;

    /** @return iterable<FieldContract> */
    public function indexFields(): iterable;

    /** @return iterable<FieldContract> */
    public function formFields(): iterable;

    /** @return iterable<FieldContract> */
    public function detailFields(): iterable;

    /** @return iterable<FilterContract> */
    public function filters(): iterable;

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable;

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable;

    /** @return iterable<AsyncAction> */
    public function asyncActions(): iterable;

    /** @return iterable<Tab> */
    public function formTabs(): iterable;

    public function defaultSort(): array;

    public function defaultFilter(): array;

    public function defaultSelect(): array;

    public function runtimeFields(): array;

    public function modifyIndexParams(array $params, GridContext $context): array;

    public function findItem(mixed $id): ?array;

    public function getList(array $params = []): array;

    public function getCount(array $filter = []): int;

    public function createItem(array $data): mixed;

    public function updateItem(mixed $id, array $data): bool;

    public function deleteItem(mixed $id): bool;

    public function save(DataWrapper $item): DataWrapper;

    public function delete(int|string $id): bool;

    public function massDelete(array $ids): void;

    public function indexPage(): IndexPage;

    public function formPage(?int $id = null): FormPage;

    public function detailPage(int $id): DetailPage;

    public function canCreate(): bool;

    public function canUpdate(?DataWrapper $item = null): bool;

    public function canDelete(?DataWrapper $item = null): bool;

    public function canView(): bool;
}
