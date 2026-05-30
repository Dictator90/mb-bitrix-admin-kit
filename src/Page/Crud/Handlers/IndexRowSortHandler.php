<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use Throwable;

/**
 * Сохраняет новый порядок строк после drag-сортировки.
 *
 * Принимает упорядоченный список id (POST `ids`), включая маркеры групп (`group:X`)
 * для сгруппированного грида. Восстанавливает порядок item-id и — если грид сгруппирован —
 * целевую группу каждого элемента (по ближайшему предшествующему `group:`), затем
 * делегирует resource->reorder() (порядок + смена группы при переносе между группами).
 */
final class IndexRowSortHandler
{
    /** @return array{success:bool,message?:string} */
    public function handle(IndexPage $page): array
    {
        if (!$page->hasResource()) {
            return ['success' => false, 'message' => 'No resource.'];
        }

        $resource = $page->resource();

        if (!$resource->canUpdate(new PermissionContext(resource: $resource, operation: 'update'))) {
            return ['success' => false, 'message' => 'Update permission denied.'];
        }

        if (!method_exists($resource, 'reorder')) {
            return ['success' => false, 'message' => 'Reorder is not supported.'];
        }

        $rawIds = $_POST['ids'] ?? $_POST['rows'] ?? null;
        if (!is_array($rawIds) || $rawIds === []) {
            return ['success' => false, 'message' => 'Empty order.'];
        }

        [$groupField, $ownerKey] = $this->resolveGrouping($resource);

        $orderedIds = [];
        $groupByItemId = [];
        $currentGroup = null;

        foreach ($rawIds as $rawId) {
            if (GridRowId::isGroupId($rawId)) {
                $group = GridRowId::rawId($rawId);
                $currentGroup = ($group === '' || $group === '__ungrouped') ? null : $group;
                continue;
            }

            $itemId = GridRowId::normalizeItemId($rawId);
            if ($itemId === null || $itemId === '') {
                continue;
            }

            $orderedIds[] = $itemId;
            if ($groupField !== null && $currentGroup !== null) {
                $groupByItemId[(string)$itemId] = $currentGroup;
            }
        }

        if ($orderedIds === []) {
            return ['success' => false, 'message' => 'Empty order.'];
        }

        try {
            $resource->reorder($orderedIds, $groupByItemId, $groupField);
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }

        return ['success' => true];
    }

    /**
     * Возвращает [FK-колонку группировки, ownerKey] или [null, 'ID'], если грид не сгруппирован.
     *
     * @return array{0:string|null,1:string}
     */
    private function resolveGrouping(object $resource): array
    {
        if (!method_exists($resource, 'indexGrouping')) {
            return [null, 'ID'];
        }

        $grouping = $resource->indexGrouping();
        if (!$grouping instanceof IndexGrouping) {
            return [null, 'ID'];
        }

        return [$grouping->foreignKey(), $grouping->ownerKey()];
    }
}
