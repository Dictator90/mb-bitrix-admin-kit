<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Objectify\EntityObject;
use InvalidArgumentException;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\HasMany;
use MB\Bitrix\AdminKit\Field\Relation\HasOne;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use RuntimeException;

final class RelationObjectMutator
{
    public function __construct(private readonly ManualPivotSynchronizer $manualPivot = new ManualPivotSynchronizer())
    {
    }

    /**
     * @param EntityObject|object $owner Bitrix EntityObject or compatible object with get/set/getId.
     */
    public function mutate(object $owner, RelationField $field, RelationMetadata $metadata, mixed $value, DbOperationContext $context): void
    {
        match ($metadata->relationType) {
            RelationType::BELONGS_TO => $this->mutateBelongsTo($owner, $field, $metadata, $value),
            RelationType::BELONGS_TO_MANY => $this->mutateBelongsToMany($owner, $field, $metadata, $value, $context),
            RelationType::HAS_ONE => $this->mutateHasOne($owner, $field, $metadata, $value),
            RelationType::HAS_MANY => $this->mutateHasMany($owner, $field, $metadata, $value),
        };
    }

    private function mutateBelongsTo(object $owner, RelationField $field, RelationMetadata $metadata, mixed $value): void
    {
        $relationName = $this->relationName($field, $metadata);
        $scalarValue = $this->normalizeScalarId($value);
        if ($scalarValue === '') {
            $scalarValue = null;
        }

        if ($metadata->foreignKey !== null && $metadata->foreignKey !== '') {
            $owner->set($metadata->foreignKey, $scalarValue);

            return;
        }

        if ($scalarValue === null) {
            $owner->set($relationName, null);

            return;
        }

        if ($metadata->relatedEntity === '' || !class_exists($metadata->relatedEntity)) {
            throw new RuntimeException('BelongsTo relation "' . $relationName . '" requires relatedEntity for object assignment.');
        }

        $relatedObject = $this->fetchRelatedObject($metadata->relatedEntity, $metadata->relatedKey, $scalarValue);
        $owner->set($relationName, $relatedObject);
    }

    private function mutateBelongsToMany(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context,
    ): void {
        if (!$field instanceof BelongsToMany) {
            throw new InvalidArgumentException('BelongsToMany field expected.');
        }

        $ids = $this->normalizeIdList($value);
        $relationName = $this->relationName($field, $metadata);

        if ($field->persistsViaPivotTable($metadata)) {
            $this->manualPivot->sync($owner, $field, $metadata, $ids, $context);

            return;
        }

        if ($metadata->relatedEntity === '' || !class_exists($metadata->relatedEntity)) {
            throw new RuntimeException('BelongsToMany relation "' . $relationName . '" requires relatedEntity.');
        }

        $collection = $this->fetchRelatedCollection($metadata->relatedEntity, $metadata->relatedKey, $ids);
        $owner->set($relationName, $collection);
    }

    private function mutateHasOne(object $owner, RelationField $field, RelationMetadata $metadata, mixed $value): void
    {
        if (!$field instanceof HasOne) {
            throw new InvalidArgumentException('HasOne field expected.');
        }

        $relationName = $this->relationName($field, $metadata);

        if ($value === null || $value === '') {
            return;
        }

        if (!is_array($value)) {
            throw new RuntimeException('HasOne relation "' . $relationName . '" supports nested array payloads only.');
        }

        if ($metadata->relatedEntity === '' || !class_exists($metadata->relatedEntity)) {
            throw new RuntimeException('HasOne relation "' . $relationName . '" requires relatedEntity.');
        }

        $relatedObject = $this->resolveExistingRelatedObject($owner, $relationName)
            ?? $this->createRelatedObject($metadata->relatedEntity);

        $this->fillRelatedObject($relatedObject, $value);

        if ($metadata->foreignKey !== null && $metadata->foreignKey !== '') {
            $ownerId = $this->extractOwnerPrimary($owner, $metadata->ownerKey);
            $relatedObject->set($metadata->foreignKey, $ownerId);
        }

        $owner->set($relationName, $relatedObject);
    }

    private function mutateHasMany(object $owner, RelationField $field, RelationMetadata $metadata, mixed $value): void
    {
        if (!$field instanceof HasMany) {
            throw new InvalidArgumentException('HasMany field expected.');
        }

        $relationName = $this->relationName($field, $metadata);

        if (!is_array($value)) {
            throw new RuntimeException('HasMany relation "' . $relationName . '" expects array payload.');
        }

        if ($metadata->relatedEntity === '' || !class_exists($metadata->relatedEntity)) {
            throw new RuntimeException('HasMany relation "' . $relationName . '" requires relatedEntity.');
        }

        if (!$field->isOrphanRemovalEnabled() && !$field->isCascadeDeleteEnabled()) {
            foreach ($value as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rowId = $row[$metadata->relatedKey] ?? $row['ID'] ?? null;
                if ($rowId !== null && $rowId !== '') {
                    $this->updateRelatedRow($metadata, $rowId, $row);
                    continue;
                }

                $this->createRelatedRow($metadata, $owner, $row);
            }

            return;
        }

        throw new RuntimeException(
            'HasMany relation "' . $relationName . '" delete/sync requires explicit orphanRemoval() or cascadeDelete().',
        );
    }

    private function relationName(RelationField $field, RelationMetadata $metadata): string
    {
        return $metadata->relationName !== '' ? $metadata->relationName : $field->getColumn();
    }

    private function normalizeScalarId(mixed $value): mixed
    {
        if ($this->isEntityObject($value)) {
            return method_exists($value, 'getId') ? $value->getId() : null;
        }

        if (is_array($value)) {
            return $value['ID'] ?? null;
        }

        return $value;
    }

    /** @return list<string> */
    private function normalizeIdList(mixed $value): array
    {
        if (!is_array($value)) {
            if ($value === null || $value === '') {
                return [];
            }

            return [(string) $value];
        }

        return array_values(array_filter(array_map('strval', $value), static fn (string $id): bool => $id !== ''));
    }

    private function fetchRelatedObject(string $relatedEntity, string $relatedKey, mixed $id): object
    {
        if (!method_exists($relatedEntity, 'query')) {
            throw new RuntimeException('Related entity "' . $relatedEntity . '" does not support query().');
        }

        $object = $relatedEntity::query()
            ->setSelect(['*'])
            ->where($relatedKey, $id)
            ->fetchObject();

        if ($object === null) {
            throw new RuntimeException('Related entity record was not found for ID "' . (string) $id . '".');
        }

        return $object;
    }

    /** @param list<string> $ids */
    private function fetchRelatedCollection(string $relatedEntity, string $relatedKey, array $ids): object
    {
        if ($ids === []) {
            return $this->createEmptyRelatedCollection($relatedEntity);
        }

        if (!method_exists($relatedEntity, 'query')) {
            throw new RuntimeException('Related entity "' . $relatedEntity . '" does not support query().');
        }

        $collection = $relatedEntity::query()
            ->setSelect(['*'])
            ->whereIn($relatedKey, $ids)
            ->fetchCollection();

        if ($collection === null) {
            throw new RuntimeException('Related collection could not be loaded.');
        }

        return $collection;
    }

    private function createEmptyRelatedCollection(string $relatedEntity): object
    {
        if (method_exists($relatedEntity, 'createCollection')) {
            return $relatedEntity::createCollection();
        }

        if (method_exists($relatedEntity, 'getEntity')) {
            $entity = $relatedEntity::getEntity();
            if (is_object($entity) && method_exists($entity, 'createCollection')) {
                return $entity->createCollection();
            }
        }

        throw new RuntimeException('Related entity "' . $relatedEntity . '" does not support empty collection creation.');
    }

    private function resolveExistingRelatedObject(object $owner, string $relationName): ?object
    {
        $existing = $owner->get($relationName);

        return is_object($existing) ? $existing : null;
    }

    private function createRelatedObject(string $relatedEntity): object
    {
        if (method_exists($relatedEntity, 'createObject')) {
            return $relatedEntity::createObject();
        }

        throw new RuntimeException('Related entity "' . $relatedEntity . '" does not support createObject().');
    }

    /** @param array<string,mixed> $data */
    private function fillRelatedObject(object $relatedObject, array $data): void
    {
        foreach ($data as $key => $itemValue) {
            if (!method_exists($relatedObject, 'set')) {
                continue;
            }

            $relatedObject->set((string) $key, $itemValue);
        }
    }

    /** @param array<string,mixed> $row */
    private function updateRelatedRow(RelationMetadata $metadata, mixed $rowId, array $row): void
    {
        if (!method_exists($metadata->relatedEntity, 'query')) {
            throw new RuntimeException('Related entity does not support update.');
        }

        $object = $metadata->relatedEntity::query()
            ->setSelect(['*'])
            ->where($metadata->relatedKey, $rowId)
            ->fetchObject();

        if ($object === null) {
            throw new RuntimeException('Related row "' . (string) $rowId . '" was not found.');
        }

        $this->fillRelatedObject($object, $row);
        if (method_exists($object, 'save')) {
            $object->save();
        }
    }

    /** @param array<string,mixed> $row */
    private function createRelatedRow(RelationMetadata $metadata, object $owner, array $row): void
    {
        if (!method_exists($metadata->relatedEntity, 'createObject')) {
            throw new RuntimeException('Related entity does not support createObject().');
        }

        $object = $metadata->relatedEntity::createObject();
        $this->fillRelatedObject($object, $row);

        if ($metadata->foreignKey !== null && $metadata->foreignKey !== '') {
            $object->set($metadata->foreignKey, $this->extractOwnerPrimary($owner, $metadata->ownerKey));
        }

        if (method_exists($object, 'save')) {
            $object->save();
        }
    }

    private function extractOwnerPrimary(object $owner, string $ownerKey): mixed
    {
        if (method_exists($owner, 'getId')) {
            $id = $owner->getId();
            if ($id !== null) {
                return $id;
            }
        }

        if (method_exists($owner, 'get')) {
            return $owner->get($ownerKey);
        }

        return null;
    }

    private function isEntityObject(mixed $value): bool
    {
        return $value instanceof EntityObject
            || (is_object($value) && method_exists($value, 'get') && method_exists($value, 'set'));
    }
}
