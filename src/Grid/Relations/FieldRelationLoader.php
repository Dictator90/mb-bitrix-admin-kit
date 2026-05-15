<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Relations;

use Closure;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class FieldRelationLoader
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @param iterable<FieldContract> $fields
     * @return array<int,array<string,mixed>>
     */
    public function load(array $rows, iterable $fields): array
    {
        foreach (AdminCollection::make($fields)->all() as $field) {
            if (!$field instanceof RelationFieldContract || !$field instanceof FieldContract) {
                continue;
            }

            $rows = $this->loadField($rows, $field);
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function loadField(array $rows, RelationFieldContract&FieldContract $field): array
    {
        $localKey = $field->relationLocalKey();
        $localIds = [];
        foreach ($rows as $row) {
            if (($row['__ROW_TYPE'] ?? 'item') === 'group' || GridRowId::isGroupId($row['__GRID_ROW_ID'] ?? null)) {
                continue;
            }
            $localId = $row[$localKey] ?? null;
            if ($localId !== null && $localId !== '') {
                $localIds[(string)$localId] = $localId;
            }
        }

        if ($localIds === []) {
            return $this->fillDefaults($rows, $field);
        }

        $relatedRows = $this->fetchRelatedRows($field, array_values($localIds));
        $valuesByLocalId = [];
        foreach ($relatedRows as $relatedRow) {
            $foreignValue = $relatedRow[$field->relationForeignKey()] ?? null;
            if ($foreignValue === null || $foreignValue === '') {
                continue;
            }

            $value = $this->resolveRelatedValue($field->relationValue(), $relatedRow);
            $key = (string)$foreignValue;
            if ($field->isToMany()) {
                $valuesByLocalId[$key] ??= [];
                $valuesByLocalId[$key][] = $value;
                continue;
            }

            if (!array_key_exists($key, $valuesByLocalId)) {
                $valuesByLocalId[$key] = $value;
            }
        }

        foreach ($rows as $index => $row) {
            if (($row['__ROW_TYPE'] ?? 'item') === 'group' || GridRowId::isGroupId($row['__GRID_ROW_ID'] ?? null)) {
                continue;
            }
            $localId = $row[$localKey] ?? null;
            $key = (string)$localId;
            $rows[$index][$field->getColumn()] = array_key_exists($key, $valuesByLocalId)
                ? $valuesByLocalId[$key]
                : $field->relationDefault();
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function fillDefaults(array $rows, RelationFieldContract&FieldContract $field): array
    {
        foreach ($rows as $index => $row) {
            if (($row['__ROW_TYPE'] ?? 'item') === 'group' || GridRowId::isGroupId($row['__GRID_ROW_ID'] ?? null)) {
                continue;
            }
            $rows[$index][$field->getColumn()] = $field->relationDefault();
        }

        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    /**
     * @param array<int,mixed> $localIds
     * @return array<int,array<string,mixed>>
     */
    private function fetchRelatedRows(RelationFieldContract $field, array $localIds): array
    {
        $value = $field->relationValue();
        $select = [$field->relationForeignKey()];
        if (is_string($value)) {
            $select[] = $value;
        }

        $filter = ['@' . $field->relationForeignKey() => $localIds];
        $customFilter = $field->relationFilter();
        if ($customFilter instanceof Closure) {
            $customFilter = $customFilter($localIds);
        }
        if (is_array($customFilter)) {
            $filter = array_replace($filter, $customFilter);
        }

        $params = [
            'select' => array_values(array_unique($select)),
            'filter' => $filter,
        ];
        if ($field->relationOrder() !== []) {
            $params['order'] = $field->relationOrder();
        }

        $result = $field->relationTableClass()::getList($params);

        return $this->resultToRows($result);
    }

    /** @param array<string,mixed> $relatedRow */
    private function resolveRelatedValue(string|Closure $value, array $relatedRow): mixed
    {
        if ($value instanceof Closure) {
            return $value($relatedRow);
        }

        return $relatedRow[$value] ?? null;
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
