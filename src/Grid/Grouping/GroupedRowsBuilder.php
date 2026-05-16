<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Grouping;

use Closure;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class GroupedRowsBuilder
{
    private string $currentPrimaryKey = 'ID';
    private const UNGROUPED_ID = '__ungrouped';
    private const MAX_PARENT_DEPTH = 20;

    /**
     * @param array<int,array<string,mixed>> $itemRows
     * @param array<int,FieldContract> $fields
     * @return array<int,array<string,mixed>>
     */
    public function build(
        array $itemRows,
        ResourceContract $itemResource,
        IndexGrouping $grouping,
        GridContext $context,
        ?IndexPageDefinitionContract $indexPage = null,
        array $fields = [],
    ): array {
        unset($context, $indexPage);
        $primaryKey = $itemResource->getPrimaryKey();
        $this->currentPrimaryKey = $primaryKey;

        $foreignKey = $grouping->foreignKey();
        [$groupIds, $itemsByGroup, $ungroupedRows] = $this->splitItems($itemRows, $foreignKey);
        $groupResource = $this->makeGroupResource($grouping->resourceClass());
        $groups = $this->loadGroups($groupResource, $grouping, $groupIds);

        if ($grouping->parentKey() !== null) {
            $groups = $this->loadParentGroups($groupResource, $grouping, $groups);
        }

        $childrenByParent = $this->childrenByParent($groups, $grouping->parentKey(), $grouping->ownerKey());
        $labelColumn = $grouping->labelColumn() ?? $this->firstFieldColumn($fields);
        $rows = [];
        $emitted = [];

        foreach ($groups as $groupId => $groupRow) {
            if ($this->hasParentInGroups($groupRow, $groups, $grouping)) {
                continue;
            }
            $this->appendGroupTree($rows, $emitted, (string)$groupId, $groups, $childrenByParent, $itemsByGroup, $grouping, $fields, $labelColumn, 0, null);
        }

        foreach ($itemsByGroup as $groupId => $groupRows) {
            if (isset($emitted[$groupId]) || !isset($groups[$groupId])) {
                continue;
            }
            $this->appendGroupTree($rows, $emitted, (string)$groupId, $groups, $childrenByParent, $itemsByGroup, $grouping, $fields, $labelColumn, 0, null);
        }

        if ($ungroupedRows !== []) {
            if ($grouping->showUngrouped()) {
                $rows[] = $this->makeUngroupedRow($grouping, $fields, $labelColumn, $ungroupedRows !== []);
                foreach ($ungroupedRows as $row) {
                    $rows[] = $this->makeItemRow($row, $grouping, 1, GridRowId::group(self::UNGROUPED_ID), $primaryKey);
                }
            } else {
                foreach ($ungroupedRows as $row) {
                    $rows[] = $row + ['__ROW_TYPE' => 'item'];
                }
            }
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{0:array<string,mixed>,1:array<string,array<int,array<string,mixed>>>,2:array<int,array<string,mixed>>}
     */
    private function splitItems(array $rows, string $foreignKey): array
    {
        $groupIds = [];
        $itemsByGroup = [];
        $ungrouped = [];
        foreach ($rows as $row) {
            $groupId = $row[$foreignKey] ?? null;
            if ($groupId === null || $groupId === '' || $groupId === 0 || $groupId === '0') {
                $ungrouped[] = $row;
                continue;
            }
            $key = (string)$groupId;
            $groupIds[$key] = $groupId;
            $itemsByGroup[$key] ??= [];
            $itemsByGroup[$key][] = $row;
        }

        return [$groupIds, $itemsByGroup, $ungrouped];
    }

    /** @param class-string $resourceClass */
    private function makeGroupResource(string $resourceClass): ResourceContract
    {
        $resource = new $resourceClass();
        if (!$resource instanceof ResourceContract) {
            throw new \LogicException(sprintf('Grouping resource "%s" must implement ResourceContract.', $resourceClass));
        }

        return $resource;
    }

    /**
     * @param array<string,mixed> $ids
     * @return array<string,array<string,mixed>>
     */
    private function loadGroups(ResourceContract $resource, IndexGrouping $grouping, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $dataManager = $resource->getDataManagerClass();
        if ($dataManager === null || $dataManager === '') {
            return [];
        }

        $params = [
            'filter' => ['@' . $grouping->ownerKey() => array_values($ids)],
            'order' => $grouping->order(),
        ];
        $rows = $this->resultToRows($dataManager::getList($params));
        $groups = [];
        foreach ($rows as $row) {
            $id = $row[$grouping->ownerKey()] ?? null;
            if ($id !== null && $id !== '') {
                $groups[(string)$id] = $row;
            }
        }

        return $groups;
    }

    /**
     * @param array<string,array<string,mixed>> $groups
     * @return array<string,array<string,mixed>>
     */
    private function loadParentGroups(ResourceContract $resource, IndexGrouping $grouping, array $groups): array
    {
        $parentKey = $grouping->parentKey();
        if ($parentKey === null) {
            return $groups;
        }

        $visited = array_fill_keys(array_keys($groups), true);
        for ($depth = 0; $depth < self::MAX_PARENT_DEPTH; $depth++) {
            $missingParentIds = [];
            foreach ($groups as $row) {
                $parentId = $row[$parentKey] ?? null;
                if ($parentId === null || $parentId === '') {
                    continue;
                }
                $key = (string)$parentId;
                if (!isset($groups[$key]) && !isset($visited[$key])) {
                    $missingParentIds[$key] = $parentId;
                    $visited[$key] = true;
                }
            }
            if ($missingParentIds === []) {
                break;
            }
            $parents = $this->loadGroups($resource, $grouping, $missingParentIds);
            if ($parents === []) {
                break;
            }
            $groups = $parents + $groups;
        }

        return $groups;
    }

    /**
     * @param array<string,array<string,mixed>> $groups
     * @return array<string,array<int,string>>
     */
    private function childrenByParent(array $groups, ?string $parentKey, string $ownerKey): array
    {
        if ($parentKey === null) {
            return [];
        }
        $children = [];
        foreach ($groups as $groupId => $group) {
            $parentId = $group[$parentKey] ?? null;
            $ownId = $group[$ownerKey] ?? $groupId;
            if ($parentId === null || $parentId === '' || (string)$parentId === (string)$ownId || !isset($groups[(string)$parentId])) {
                continue;
            }
            $children[(string)$parentId][] = $groupId;
        }

        return $children;
    }

    /** @param array<string,array<string,mixed>> $groups */
    /**
     * @param array<string,mixed> $groupRow
     * @param array<string,array<string,mixed>> $groups
     */
    private function hasParentInGroups(array $groupRow, array $groups, IndexGrouping $grouping): bool
    {
        $parentKey = $grouping->parentKey();
        if ($parentKey === null) {
            return false;
        }
        $parentId = $groupRow[$parentKey] ?? null;
        $ownId = $groupRow[$grouping->ownerKey()] ?? null;

        return $parentId !== null && $parentId !== '' && (string)$parentId !== (string)$ownId && isset($groups[(string)$parentId]);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<string,bool> $emitted
     * @param array<string,array<string,mixed>> $groups
     * @param array<string,array<int,string>> $childrenByParent
     * @param array<string,array<int,array<string,mixed>>> $itemsByGroup
     * @param array<int,FieldContract> $fields
     */
    private function appendGroupTree(array &$rows, array &$emitted, string $groupId, array $groups, array $childrenByParent, array $itemsByGroup, IndexGrouping $grouping, array $fields, ?string $labelColumn, int $depth, ?string $parentGridId): void
    {
        if (isset($emitted[$groupId]) || !isset($groups[$groupId]) || $depth > self::MAX_PARENT_DEPTH) {
            return;
        }

        $emitted[$groupId] = true;
        $childIds = $childrenByParent[$groupId] ?? [];
        $items = $itemsByGroup[$groupId] ?? [];
        $gridRowId = GridRowId::group($groupId);
        $rows[] = $this->makeGroupRow($groups[$groupId], $grouping, $fields, $labelColumn, $depth, $parentGridId, $childIds !== [] || $items !== []);

        foreach ($childIds as $childId) {
            $this->appendGroupTree($rows, $emitted, (string)$childId, $groups, $childrenByParent, $itemsByGroup, $grouping, $fields, $labelColumn, $depth + 1, $gridRowId);
        }
        foreach ($items as $itemRow) {
            $rows[] = $this->makeItemRow($itemRow, $grouping, $depth + 1, $gridRowId, $this->currentPrimaryKey);
        }
    }

    /**
     * @param array<string,mixed> $groupRow
     * @param array<int,FieldContract> $fields
     * @return array<string,mixed>
     */
    private function makeGroupRow(array $groupRow, IndexGrouping $grouping, array $fields, ?string $labelColumn, int $depth, ?string $parentGridId, bool $hasChildren): array
    {
        $id = $groupRow[$grouping->ownerKey()] ?? '';
        $row = $this->emptyFieldColumns($fields);
        if ($labelColumn !== null) {
            $row[$labelColumn] = $this->resolveLabel($grouping->label(), $groupRow, $labelColumn);
        }
        $row['__ROW_TYPE'] = 'group';
        $row['__GROUP_ID'] = $id;
        $row['__GROUP_RESOURCE'] = $grouping->resourceClass();
        $row['__GROUP_DATA'] = $groupRow;
        $row['__GRID_ROW_ID'] = GridRowId::group($id);
        $meta = [
            'has_child' => $hasChildren,
            'expand' => $grouping->expand(),
            'depth' => $depth > 0 ? $depth : null,
            'parent_id' => $parentGridId ?? 0,
        ];
        if (!$grouping->fullWidth()) {
            $meta['shift'] = true;
        }

        $row['__adminkit_grid_row'] = array_filter($meta, static fn (mixed $value): bool => $value !== null);

        return $row;
    }

    /**
     * @param array<int,FieldContract> $fields
     * @return array<string,mixed>
     */
    private function makeUngroupedRow(IndexGrouping $grouping, array $fields, ?string $labelColumn, bool $hasChildren): array
    {
        $label = $grouping->ungroupedLabel();
        if ($label instanceof Closure) {
            $label = $label([]);
        }
        $row = $this->emptyFieldColumns($fields);
        if ($labelColumn !== null) {
            $row[$labelColumn] = $label ?? 'Без группы';
        }
        $row['__ROW_TYPE'] = 'group';
        $row['__GROUP_ID'] = self::UNGROUPED_ID;
        $row['__GROUP_RESOURCE'] = $grouping->resourceClass();
        $row['__GROUP_DATA'] = [];
        $row['__GRID_ROW_ID'] = GridRowId::group(self::UNGROUPED_ID);
        $meta = [
            'has_child' => $hasChildren,
            'expand' => $grouping->expand(),
        ];
        if (!$grouping->fullWidth()) {
            $meta['shift'] = true;
        }

        $row['__adminkit_grid_row'] = $meta;

        return $row;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function makeItemRow(array $row, IndexGrouping $grouping, int $depth, string $parentGridId, string $primaryKey): array
    {
        $id = $row['__REAL_ID'] ?? $row[$primaryKey] ?? null;
        $row['__ROW_TYPE'] = 'item';
        $row['__REAL_ID'] = $id;
        if ($id !== null && $id !== '') {
            $row['__GRID_ROW_ID'] = GridRowId::item($id);
        }
        $meta = [
            'shift' => true,
            'depth' => $depth,
            'parent_id' => $parentGridId,
        ];
        if ($grouping->fullWidth()) {
            $meta['group_id'] = GridRowId::rawId($parentGridId);
            $meta['parent_group_id'] = GridRowId::rawId($parentGridId);
        }

        $row['__adminkit_grid_row'] = $meta;

        return $row;
    }

    /** @return array<string,mixed> */
    /**
     * @param array<int,FieldContract> $fields
     * @return array<string,mixed>
     */
    private function emptyFieldColumns(array $fields): array
    {
        $row = [];
        foreach (AdminCollection::make($fields)->all() as $field) {
            if ($field instanceof FieldContract) {
                $row[$field->getColumn()] = null;
            }
        }

        return $row;
    }

    /** @param array<int,FieldContract> $fields */
    private function firstFieldColumn(array $fields): ?string
    {
        foreach (AdminCollection::make($fields)->all() as $field) {
            if ($field instanceof FieldContract) {
                return $field->getColumn();
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $groupRow
     */
    private function resolveLabel(string|Closure|null $label, array $groupRow, ?string $fallbackColumn): mixed
    {
        if ($label instanceof Closure) {
            return $label($groupRow);
        }
        if (is_string($label)) {
            return $groupRow[$label] ?? null;
        }

        return $fallbackColumn !== null ? ($groupRow[$fallbackColumn] ?? null) : null;
    }

    /** @return array<int,array<string,mixed>> */
    private function resultToRows(mixed $result): array
    {
        if (is_object($result) && method_exists($result, 'fetchAll')) {
            $rows = $result->fetchAll();
            return is_array($rows) ? $rows : [];
        }
        $rows = [];
        if (is_object($result) && method_exists($result, 'fetch')) {
            while (($row = $result->fetch()) !== false) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }
}
