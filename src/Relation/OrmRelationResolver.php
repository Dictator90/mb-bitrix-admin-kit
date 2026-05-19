<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Contracts\Relation\RelationResolverInterface;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use RuntimeException;

final class OrmRelationResolver implements RelationResolverInterface
{
    public function resolve(string $ownerDataManagerClass, RelationField $field): ?RelationMetadata
    {
        if (!method_exists($ownerDataManagerClass, 'getEntity')) {
            return null;
        }

        $relationName = $field->relationName() ?: $field->getColumn();
        if ($relationName === '') {
            return null;
        }

        /** @var object $entity */
        $entity = $ownerDataManagerClass::getEntity();
        if (!is_object($entity) || !method_exists($entity, 'hasField') || !$entity->hasField($relationName) || !method_exists($entity, 'getField')) {
            return null;
        }

        $ormField = $entity->getField($relationName);
        $fieldClass = get_class($ormField);
        $ormType = $this->detectOrmRelationType($fieldClass, $ormField);
        $this->assertCompatibleTypes($field->relationType(), $ormType, $relationName);

        $relatedEntity = method_exists($ormField, 'getRefEntity') && $ormField->getRefEntity() !== null
            ? $ormField->getRefEntity()->getDataClass()
            : '';

        $multiple = method_exists($ormField, 'isMultiple') ? (bool) $ormField->isMultiple() : false;

        return new RelationMetadata(
            relationType: $field->relationType(),
            ownerEntity: $ownerDataManagerClass,
            relatedEntity: $relatedEntity,
            mediatorEntity: str_contains($fieldClass, 'ManyToMany') ? ($ormField->getMediatorEntity()?->getDataClass() ?? null) : null,
            multiple: $multiple,
            relationName: $relationName,
            cascadeSave: $field->isCascadeSaveEnabled(),
            cascadeDelete: $field->isCascadeDeleteEnabled(),
            orphanRemoval: $field->isOrphanRemovalEnabled(),
            ormFieldClass: $fieldClass,
            ormDetectedType: $ormType,
        );
    }

    private function detectOrmRelationType(string $fieldClass, object $ormField): ?RelationType
    {
        if (str_contains($fieldClass, 'ManyToMany')) {
            return RelationType::BELONGS_TO_MANY;
        }

        if (str_contains($fieldClass, 'OneToMany')) {
            return RelationType::HAS_MANY;
        }

        if (str_contains($fieldClass, 'Reference')) {
            if (method_exists($ormField, 'isOneToOne') && (bool) $ormField->isOneToOne()) {
                return RelationType::HAS_ONE;
            }

            return RelationType::BELONGS_TO;
        }

        return null;
    }

    private function assertCompatibleTypes(RelationType $declaredType, ?RelationType $ormType, string $relationName): void
    {
        if ($ormType === null) {
            return;
        }

        if ($declaredType === RelationType::BELONGS_TO && $ormType === RelationType::BELONGS_TO_MANY) {
            throw new RuntimeException('Relation "' . $relationName . '" is ManyToMany and cannot be used as BelongsTo.');
        }

        if ($declaredType === RelationType::BELONGS_TO_MANY && $ormType !== RelationType::BELONGS_TO_MANY) {
            throw new RuntimeException('Relation "' . $relationName . '" must be ManyToMany for BelongsToMany field.');
        }

        if ($declaredType === RelationType::HAS_MANY && $ormType !== RelationType::HAS_MANY) {
            throw new RuntimeException('Relation "' . $relationName . '" must be OneToMany for HasMany field.');
        }
    }
}
