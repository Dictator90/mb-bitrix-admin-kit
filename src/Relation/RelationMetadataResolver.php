<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class RelationMetadataResolver
{
    public function __construct(
        private readonly OrmRelationResolver $ormResolver = new OrmRelationResolver(),
        private readonly ExplicitRelationResolver $explicitResolver = new ExplicitRelationResolver(),
        private readonly RuntimeRelationRegistrar $runtimeRegistrar = new RuntimeRelationRegistrar(),
    ) {
    }

    public function resolve(string $ownerDataManagerClass, RelationField $field, bool $registerRuntime = true): ?RelationMetadata
    {
        if ($registerRuntime && $field->hasExplicitRelationDefinition()) {
            $this->runtimeRegistrar->register($ownerDataManagerClass, $field);
        }

        if ($field->hasExplicitRelationDefinition()) {
            $explicit = $this->explicitResolver->resolve($ownerDataManagerClass, $field);
            if ($explicit !== null) {
                return $this->withResolvedPivotKeys($explicit);
            }
        }

        $orm = $this->ormResolver->resolve($ownerDataManagerClass, $field);

        return $orm !== null ? $this->withResolvedPivotKeys($orm) : null;
    }

    private function withResolvedPivotKeys(RelationMetadata $metadata): RelationMetadata
    {
        $metadata = $this->orientMediatorReferences($metadata);

        if (
            $metadata->foreignPivotKey !== null
            && $metadata->foreignPivotKey !== ''
            && $metadata->relatedPivotKey !== null
            && $metadata->relatedPivotKey !== ''
        ) {
            return $metadata;
        }

        if (
            $metadata->mediatorEntity === null
            || $metadata->mediatorEntity === ''
            || $metadata->localMediatorReference === ''
            || $metadata->remoteMediatorReference === ''
        ) {
            return $metadata;
        }

        $resolved = MediatorPivotKeyResolver::resolve(
            $metadata->mediatorEntity,
            $metadata->localMediatorReference,
            $metadata->remoteMediatorReference,
        );

        if ($resolved === null) {
            return $metadata;
        }

        return new RelationMetadata(
            relationType: $metadata->relationType,
            ownerEntity: $metadata->ownerEntity,
            relatedEntity: $metadata->relatedEntity,
            mediatorEntity: $metadata->mediatorEntity,
            foreignKey: $metadata->foreignKey,
            ownerKey: $metadata->ownerKey,
            relatedKey: $metadata->relatedKey,
            foreignPivotKey: $metadata->foreignPivotKey ?? $resolved[0],
            relatedPivotKey: $metadata->relatedPivotKey ?? $resolved[1],
            localMediatorReference: $metadata->localMediatorReference,
            remoteMediatorReference: $metadata->remoteMediatorReference,
            multiple: $metadata->multiple,
            cascadeSave: $metadata->cascadeSave,
            cascadeDelete: $metadata->cascadeDelete,
            orphanRemoval: $metadata->orphanRemoval,
            relationName: $metadata->relationName,
            ormFieldClass: $metadata->ormFieldClass,
            ormDetectedType: $metadata->ormDetectedType,
        );
    }

    private function orientMediatorReferences(RelationMetadata $metadata): RelationMetadata
    {
        if (
            $metadata->localMediatorReference === ''
            || $metadata->remoteMediatorReference === ''
            || $metadata->mediatorEntity === null
            || $metadata->mediatorEntity === ''
            || $metadata->ownerEntity === ''
            || $metadata->relatedEntity === ''
        ) {
            return $metadata;
        }

        [$localReference, $remoteReference] = MediatorReferenceOrientation::orient(
            $metadata->mediatorEntity,
            $metadata->ownerEntity,
            $metadata->relatedEntity,
            $metadata->localMediatorReference,
            $metadata->remoteMediatorReference,
        );

        if (
            $localReference === $metadata->localMediatorReference
            && $remoteReference === $metadata->remoteMediatorReference
        ) {
            return $metadata;
        }

        return new RelationMetadata(
            relationType: $metadata->relationType,
            ownerEntity: $metadata->ownerEntity,
            relatedEntity: $metadata->relatedEntity,
            mediatorEntity: $metadata->mediatorEntity,
            foreignKey: $metadata->foreignKey,
            ownerKey: $metadata->ownerKey,
            relatedKey: $metadata->relatedKey,
            foreignPivotKey: $metadata->foreignPivotKey,
            relatedPivotKey: $metadata->relatedPivotKey,
            localMediatorReference: $localReference,
            remoteMediatorReference: $remoteReference,
            multiple: $metadata->multiple,
            cascadeSave: $metadata->cascadeSave,
            cascadeDelete: $metadata->cascadeDelete,
            orphanRemoval: $metadata->orphanRemoval,
            relationName: $metadata->relationName,
            ormFieldClass: $metadata->ormFieldClass,
            ormDetectedType: $metadata->ormDetectedType,
        );
    }

    /**
     * @param iterable<RelationField> $fields
     * @return list<string>
     */
    public function relationSelects(string $ownerDataManagerClass, iterable $fields, bool $registerRuntime = true): array
    {
        $select = ['*'];

        foreach ($fields as $field) {
            if (!$field instanceof RelationField) {
                continue;
            }

            $metadata = $this->resolve($ownerDataManagerClass, $field, $registerRuntime);
            if ($metadata === null || $metadata->relationName === '') {
                continue;
            }

            if ($field instanceof BelongsToMany && !$field->hasMediatorReferences()) {
                continue;
            }

            $select[] = $metadata->relationName;
        }

        return array_values(array_unique($select));
    }
}
