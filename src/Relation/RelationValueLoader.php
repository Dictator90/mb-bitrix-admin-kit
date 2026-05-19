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
            return $this->loadHasMany($item, $name);
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

    /** @return array<int|string,mixed> */
    private function loadHasMany(mixed $item, string $name): array
    {
        $value = $this->readRawValue($item, $name, $name);

        if ($this->isCollection($value)) {
            return $this->collectionToArray($value);
        }

        if (is_array($value)) {
            return $value;
        }

        return $value === null ? [] : [$value];
    }

    /** @return list<string> */
    private function loadBelongsToMany(mixed $item, BelongsToMany $field, RelationMetadata $metadata, string $name): array
    {
        $value = $this->readRawValue($item, $field->getColumn(), $name);

        if ($field->isOrmRelationMode()) {
            if ($this->isCollection($value)) {
                return $this->collectionToIds($value, $metadata->relatedKey);
            }

            if (is_array($value)) {
                return $this->normalizeIdsFromArray($value, $metadata->relatedKey);
            }

            return [];
        }

        return $this->parseCsvIds($value);
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
                // relation may be stored in scalar column fallback
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

    /** @return array<int|string,mixed> */
    private function collectionToArray(object $collection): array
    {
        $rows = [];

        foreach ($collection as $item) {
            if ($this->isEntityObject($item) && method_exists($item, 'collectValues')) {
                $rows[] = $item->collectValues();
                continue;
            }

            if (is_array($item)) {
                $rows[] = $item;
                continue;
            }

            $rows[] = $item;
        }

        return $rows;
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
