<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Contracts\Relation\RelationResolverInterface;
use MB\Bitrix\AdminKit\Field\RelationField;

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
        );
    }
}
