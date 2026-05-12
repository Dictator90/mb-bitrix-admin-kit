<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Support\DataWrapper;

interface ResourceContract
{
    public function getTitle(): string;

    public function getDataManagerClass(): ?string;

    public function getPrimaryKey(): string;

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

    /** @return iterable<\MB\Bitrix\AdminKit\Action\AsyncAction> */
    public function asyncActions(): iterable;

    /** @return iterable<\MB\Bitrix\AdminKit\Support\Tab> */
    public function formTabs(): iterable;

    public function findItem(int|string $id): ?DataWrapper;

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
