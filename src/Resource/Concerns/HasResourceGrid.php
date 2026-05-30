<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminString;

trait HasResourceGrid
{
    public function getGridId(): string
    {
        $dm = method_exists($this, 'getDataManagerClass') ? $this->getDataManagerClass() : null;

        return AdminString::gridId($dm ?: static::class);
    }

    public function getFilterId(): string
    {
        $dm = method_exists($this, 'getDataManagerClass') ? $this->getDataManagerClass() : null;

        return AdminString::filterId($dm ?: static::class);
    }

    public function useTotalCount(GridContext $context): bool
    {
        return true;
    }

    public function countCacheTtl(GridContext $context): int
    {
        return 0;
    }

    public function maxPageSize(): int
    {
        return 200;
    }

    public function showPagination(): bool
    {
        return true;
    }

    public function bulkChunkSize(): int
    {
        return 100;
    }

    // --- Grid flags / modes (см. GridSettings + BitrixGridAdapter) ---

    public function allowColumnsSort(): bool
    {
        return true;
    }

    public function allowColumnsResize(): bool
    {
        return true;
    }

    public function allowHorizontalScroll(): bool
    {
        return true;
    }

    /**
     * Включает перетаскивание строк в UI грида.
     * ВНИМАНИЕ: сохранение нового порядка — отдельная фича (см. roadmap row drag-sort);
     * сам по себе флаг порядок не персистит.
     */
    public function allowRowsSort(): bool
    {
        return false;
    }

    public function allowContextMenu(): bool
    {
        return false;
    }

    public function pinHeader(): bool
    {
        return false;
    }

    public function stickedColumns(): bool
    {
        return false;
    }

    public function showGridSettingsMenu(): bool
    {
        return true;
    }

    public function enableFieldsSearch(): bool
    {
        return false;
    }

    public function showSelectedCounter(): bool
    {
        return true;
    }

    public function showTotalCounter(): bool
    {
        return true;
    }

    public function useAjax(): bool
    {
        return true;
    }

    /** @return int[] */
    public function pageSizes(): array
    {
        return [];
    }

    public function gridEmptyMessage(): ?string
    {
        return null;
    }

    /** @return array<int|string,mixed> */
    public function gridAggregates(): array
    {
        return [];
    }

    /** @return array<int|string,mixed> */
    public function gridFooter(): array
    {
        return [];
    }

    public function tileMode(): bool
    {
        return false;
    }

    public function tileSize(): ?string
    {
        return null;
    }

    public function tileItemJsClass(): ?string
    {
        return null;
    }

    public function rowLayout(): ?string
    {
        return null;
    }

    /**
     * Поле сортировки для drag-сортировки строк (например 'SORT').
     * null — сохранение порядка не поддерживается (drag только визуальный).
     */
    public function sortField(): ?string
    {
        return null;
    }

    /**
     * Шаг инкремента значений сортировки в дефолтном reorder() (100, 200, …).
     */
    public function sortStep(): int
    {
        return 100;
    }

    /**
     * Сохраняет новый порядок строк: записывает в sortField() инкрементальные значения
     * в порядке $orderedIds. По умолчанию обновляет напрямую через DataManager (минуя
     * form-pipeline/валидацию). Переопределите для кастомной логики.
     *
     * Поддерживает кросс-групповой перенос: если грид сгруппирован, $groupByItemId
     * содержит целевую группу для каждого item-id, а $groupField — FK колонку группировки.
     * В этом случае вместе с порядком обновляется и принадлежность к группе.
     *
     * @param array<int,int|string> $orderedIds  item-id в новом порядке
     * @param array<string,int|string> $groupByItemId  карта itemId => targetGroupId (для сгруппированного грида)
     * @param string|null $groupField  FK колонка группировки (например 'TYPE_ID')
     */
    public function reorder(array $orderedIds, array $groupByItemId = [], ?string $groupField = null): void
    {
        $field = $this->sortField();
        if ($field === null || $field === '' || $orderedIds === []) {
            return;
        }

        $dataManager = method_exists($this, 'getDataManagerClass') ? $this->getDataManagerClass() : null;
        if ($dataManager === null || $dataManager === '') {
            return;
        }

        $applyGroup = $groupField !== null && $groupField !== '' && $groupByItemId !== [];

        $step = max(1, $this->sortStep());
        $sort = $step;
        foreach ($orderedIds as $id) {
            $intId = (int)$id;
            if ($intId > 0) {
                $data = [$field => $sort];
                if ($applyGroup && isset($groupByItemId[(string)$id])) {
                    $data[$groupField] = $groupByItemId[(string)$id];
                }
                try {
                    $dataManager::update($intId, $data);
                } catch (\Throwable) {
                    // пропускаем проблемную строку, продолжаем остальные
                }
            }
            $sort += $step;
        }
    }
}
