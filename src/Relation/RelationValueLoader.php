<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Objectify\EntityObject;
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\HasMany;
use MB\Bitrix\AdminKit\Field\Relation\HasOne;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class RelationValueLoader
{
    public function load(mixed $item, RelationField $field, RelationMetadata $metadata): mixed
    {
        $name = $this->resolveFieldName($field, $metadata);

        if ($field instanceof BelongsToMany) {
            return $this->loadBelongsToMany($item, $field, $metadata, $name);
        }

        if ($field instanceof HasMany) {
            return $this->loadHasMany($item, $field, $metadata, $name);
        }

        if ($field instanceof HasOne) {
            return $this->loadHasOne($item, $name);
        }

        if ($field instanceof BelongsTo) {
            return $this->loadBelongsTo($item, $field, $metadata, $name);
        }

        return $this->readRawValue($item, $field->getColumn(), $name);
    }

    private function resolveFieldName(RelationField $field, RelationMetadata $metadata): string
    {
        return $metadata->relationName !== '' ? $metadata->relationName : $field->getColumn();
    }

    private function loadBelongsTo(mixed $item, BelongsTo $field, RelationMetadata $metadata, string $name): mixed
    {
        $value = $this->readRawValue($item, $field->getColumn(), $name);

        if ($this->isEntityObject($value)) {
            return $this->extractEntityObjectId($value, $metadata->relatedKey);
        }

        if ($value !== null && $value !== '') {
            return $value;
        }

        if ($metadata->foreignKey !== null && $metadata->foreignKey !== '' && is_array($item)) {
            return $item[$metadata->foreignKey] ?? null;
        }

        if ($metadata->foreignKey !== null && $metadata->foreignKey !== '' && $this->isEntityObject($item)) {
            return $item->get($metadata->foreignKey);
        }

        return $value;
    }

    private function loadHasOne(mixed $item, string $name): mixed
    {
        $value = $this->readRawValue($item, $name, $name);

        if ($this->isEntityObject($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return $value;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadHasMany(mixed $item, HasMany $field, RelationMetadata $metadata, string $name): array
    {
        $rows = $this->expandRelationRows($this->readRawValue($item, $name, $name));

        if (!$field->hasExplicitRelationDefinition()) {
            return $rows;
        }

        $fromTable = $this->loadHasManyFromRelatedTable($item, $field, $metadata);
        if ($fromTable === []) {
            return $rows;
        }

        if ($rows === [] || count($fromTable) > count($rows)) {
            return $fromTable;
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expandRelationRows(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_object($value) && method_exists($value, 'getAll')) {
            $all = $value->getAll();
            if (is_array($all)) {
                return $this->expandRelationRows($all);
            }
        }

        if ($this->isCollection($value) || ($value instanceof \Traversable && !$value instanceof \ArrayObject)) {
            $rows = [];
            foreach ($value as $item) {
                $normalized = $this->normalizeRelationRow($item);
                if (is_array($normalized) && $normalized !== []) {
                    $rows[] = $normalized;
                }
            }

            return $rows;
        }

        if (is_array($value)) {
            if ($this->isListArray($value)) {
                $rows = [];
                foreach ($value as $item) {
                    $normalized = $this->normalizeRelationRow($item);
                    if (is_array($normalized) && $normalized !== []) {
                        $rows[] = $normalized;
                    }
                }

                return $rows;
            }

            $single = $this->normalizeRelationRow($value);

            return is_array($single) && $single !== [] ? [$single] : [];
        }

        $single = $this->normalizeRelationRow($value);

        return is_array($single) && $single !== [] ? [$single] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadHasManyFromRelatedTable(mixed $item, HasMany $field, RelationMetadata $metadata): array
    {
        $relatedClass = $metadata->relatedEntity;
        $foreignKey = $metadata->foreignKey;

        if (
            $relatedClass === ''
            || $foreignKey === null
            || $foreignKey === ''
            || !method_exists($relatedClass, 'getList')
        ) {
            return [];
        }

        $ownerId = $this->extractOwnerIdFromItem($item, $metadata->ownerKey);
        if ($ownerId === null || $ownerId === '') {
            return [];
        }

        $params = [
            'filter' => [$foreignKey => $ownerId],
        ];

        $select = $field->getTablePreviewColumnNames();
        if ($select !== []) {
            $params['select'] = $select;
        }

        $rows = [];
        $result = $relatedClass::getList($params);

        while (is_object($result) && method_exists($result, 'fetch')) {
            $row = $result->fetch();
            if (!is_array($row)) {
                break;
            }

            $rows[] = $this->scalarizeRowValues($row);
        }

        return $rows;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /** @return list<string> */
    private function loadBelongsToMany(mixed $item, BelongsToMany $field, RelationMetadata $metadata, string $name): array
    {
        $value = $this->readRawValue($item, $field->getColumn(), $name);

        if ($field->isOrmRelationMode()) {
            if ($this->isCollection($value)) {
                $ids = $this->collectionToIds($value, $metadata->relatedKey);
                if ($ids !== [] || !$field->persistsViaPivotTable($metadata)) {
                    return $ids;
                }
            } elseif (is_array($value)) {
                $ids = $this->normalizeIdsFromArray($value, $metadata->relatedKey);
                if ($ids !== [] || !$field->persistsViaPivotTable($metadata)) {
                    return $ids;
                }
            }

            if ($field->persistsViaPivotTable($metadata)) {
                return $this->loadPivotRelatedIds($item, $metadata);
            }

            return [];
        }

        return $this->parseCsvIds($value);
    }

    /** @return list<string> */
    private function loadPivotRelatedIds(mixed $item, RelationMetadata $metadata): array
    {
        $pivotTableClass = $metadata->mediatorEntity;
        $ownerColumn = $metadata->foreignPivotKey;
        $relatedColumn = $metadata->relatedPivotKey;
        $ownerKey = $metadata->ownerKey;

        if (
            $pivotTableClass === null
            || $pivotTableClass === ''
            || $ownerColumn === null
            || $ownerColumn === ''
            || $relatedColumn === null
            || $relatedColumn === ''
            || !method_exists($pivotTableClass, 'getList')
        ) {
            return [];
        }

        $ownerId = $this->extractOwnerIdFromItem($item, $ownerKey);
        if ($ownerId === null || $ownerId === '') {
            return [];
        }

        $ids = [];
        $result = $pivotTableClass::getList([
            'select' => [$relatedColumn],
            'filter' => [$ownerColumn => $ownerId],
        ]);

        while (is_object($result) && method_exists($result, 'fetch')) {
            $row = $result->fetch();
            if (!is_array($row)) {
                break;
            }

            $id = $row[$relatedColumn] ?? null;
            if ($id !== null && $id !== '') {
                $ids[] = (string) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function extractOwnerIdFromItem(mixed $item, string $ownerKey): mixed
    {
        if (is_array($item)) {
            return $item[$ownerKey] ?? $item['ID'] ?? null;
        }

        if ($item instanceof EntityObject || (is_object($item) && method_exists($item, 'get'))) {
            $id = method_exists($item, 'getId') ? $item->getId() : null;
            if ($id !== null && $id !== '') {
                return $id;
            }

            return $item->get($ownerKey);
        }

        return null;
    }

    private function readRawValue(mixed $item, string $column, string $relationName): mixed
    {
        if (is_array($item)) {
            return $item[$relationName] ?? $item[$column] ?? null;
        }

        if ($item instanceof EntityObject) {
            try {
                return $item->get($relationName);
            } catch (\Throwable) {
            }

            $entity = $item->getEntity();
            if ($entity instanceof \Bitrix\Main\ORM\Entity\Entity && $entity->hasField($column)) {
                return $item->get($column);
            }
        }

        if ($this->isEntityObject($item) && method_exists($item, 'get')) {
            return $item->get($relationName);
        }

        return null;
    }

    /** @return list<string> */
    private function parseCsvIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('strval', explode(',', $value))));
        }

        return [];
    }

    /**
     * @param array<int|string, mixed> $rows
     * @return list<string>
     */
    private function normalizeIdsFromArray(array $rows, string $relatedKey): array
    {
        $ids = [];

        foreach ($rows as $row) {
            if (is_scalar($row)) {
                $ids[] = (string) $row;
                continue;
            }

            if (is_array($row)) {
                $ids[] = (string) ($row[$relatedKey] ?? $row['ID'] ?? '');
                continue;
            }

            if ($this->isEntityObject($row)) {
                $ids[] = $this->extractEntityObjectId($row, $relatedKey);
            }
        }

        return array_values(array_filter($ids, static fn (string $id): bool => $id !== ''));
    }

    /** @return list<string> */
    private function collectionToIds(object $collection, string $relatedKey): array
    {
        $ids = [];

        foreach ($collection as $item) {
            if ($this->isEntityObject($item)) {
                $ids[] = $this->extractEntityObjectId($item, $relatedKey);
                continue;
            }

            if (is_array($item)) {
                $ids[] = (string) ($item[$relatedKey] ?? $item['ID'] ?? '');
            }
        }

        return array_values(array_filter($ids, static fn (string $id): bool => $id !== ''));
    }


    /**
     * @return array<string, mixed>|mixed
     */
    private function normalizeRelationRow(mixed $row): mixed
    {
        if (is_array($row)) {
            return $this->scalarizeRowValues($row);
        }

        if ($this->isEntityObject($row)) {
            return $this->entityObjectToRow($row);
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function entityObjectToRow(object $object): array
    {
        if (method_exists($object, 'collectValues')) {
            $values = $object->collectValues();
            if (is_array($values)) {
                return $this->scalarizeRowValues($values);
            }
        }

        if (!method_exists($object, 'get')) {
            return [];
        }

        $row = [];
        if (method_exists($object, 'getId')) {
            $id = $object->getId();
            if ($id !== null && $id !== '') {
                $row['ID'] = $id;
            }
        }

        $entity = method_exists($object, 'getEntity') ? $object->getEntity() : null;
        if ($entity !== null && method_exists($entity, 'getFields')) {
            foreach ($entity->getFields() as $field) {
                if (!method_exists($field, 'getName')) {
                    continue;
                }

                $name = (string) $field->getName();
                try {
                    $fieldValue = $object->get($name);
                } catch (\Throwable) {
                    continue;
                }

                if ($fieldValue === null || is_scalar($fieldValue)) {
                    $row[$name] = $fieldValue;
                    continue;
                }

                if (is_object($fieldValue) && method_exists($fieldValue, 'getId')) {
                    $relatedId = $fieldValue->getId();
                    if ($relatedId !== null && $relatedId !== '') {
                        $row[$name] = $relatedId;
                    }
                }
            }

            return $row;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function scalarizeRowValues(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if ($value === null || is_scalar($value)) {
                $normalized[(string) $key] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, 'getId')) {
                $relatedId = $value->getId();
                if ($relatedId !== null && $relatedId !== '') {
                    $normalized[(string) $key] = $relatedId;
                }
            }
        }

        return $normalized;
    }

    private function extractEntityObjectId(object $object, string $relatedKey): string
    {
        if (method_exists($object, 'getId')) {
            $id = $object->getId();
            if ($id !== null && $id !== '') {
                return (string) $id;
            }
        }

        if (method_exists($object, 'get')) {
            $value = $object->get($relatedKey !== '' ? $relatedKey : 'ID');

            return $value !== null ? (string) $value : '';
        }

        return '';
    }

    private function isEntityObject(mixed $value): bool
    {
        return is_object($value) && (
            $value instanceof EntityObject
            || method_exists($value, 'get')
        );
    }

    private function isCollection(mixed $value): bool
    {
        return is_object($value) && (
            is_a($value, 'Bitrix\\Main\\ORM\\Objectify\\Collection', false)
            || ($value instanceof \Traversable && method_exists($value, 'count'))
        );
    }
}
