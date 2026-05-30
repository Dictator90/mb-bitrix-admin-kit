<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Grid\GridContext;

interface ResourceGridContract
{
    public function getGridId(): string;

    public function getFilterId(): string;

    public function useTotalCount(GridContext $context): bool;

    public function countCacheTtl(GridContext $context): int;

    public function maxPageSize(): int;

    public function showPagination(): bool;

    public function bulkChunkSize(): int;

    // --- Grid flags / modes (маппятся на bitrix:main.ui.grid через GridSettings) ---

    public function allowColumnsSort(): bool;

    public function allowColumnsResize(): bool;

    public function allowHorizontalScroll(): bool;

    public function allowRowsSort(): bool;

    public function allowContextMenu(): bool;

    public function pinHeader(): bool;

    public function stickedColumns(): bool;

    public function showGridSettingsMenu(): bool;

    public function enableFieldsSearch(): bool;

    public function showSelectedCounter(): bool;

    public function showTotalCounter(): bool;

    public function useAjax(): bool;

    /** @return int[] */
    public function pageSizes(): array;

    public function gridEmptyMessage(): ?string;

    /** @return array<int|string,mixed> */
    public function gridAggregates(): array;

    /** @return array<int|string,mixed> */
    public function gridFooter(): array;

    public function tileMode(): bool;

    public function tileSize(): ?string;

    public function tileItemJsClass(): ?string;

    public function rowLayout(): ?string;

    public function sortField(): ?string;

    public function sortStep(): int;

    /**
     * @param array<int,int|string> $orderedIds
     * @param array<string,int|string> $groupByItemId
     */
    public function reorder(array $orderedIds, array $groupByItemId = [], ?string $groupField = null): void;
}
