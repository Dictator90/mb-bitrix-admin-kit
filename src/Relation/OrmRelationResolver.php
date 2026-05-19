<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
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
        $keys = $this->extractRelationKeys($ormField, $fieldClass);

        return new RelationMetadata(
            relationType: $field->relationType(),
            ownerEntity: $ownerDataManagerClass,
            relatedEntity: $relatedEntity,
            mediatorEntity: $keys['mediatorEntity'],
            foreignKey: $keys['foreignKey'],
            ownerKey: $keys['ownerKey'],
            relatedKey: $keys['relatedKey'],
            foreignPivotKey: $keys['foreignPivotKey'],
            relatedPivotKey: $keys['relatedPivotKey'],
            multiple: $multiple,
            cascadeSave: $field->isCascadeSaveEnabled(),
            cascadeDelete: $field->isCascadeDeleteEnabled(),
            orphanRemoval: $field->isOrphanRemovalEnabled(),
            relationName: $relationName,
            ormFieldClass: $fieldClass,
            ormDetectedType: $ormType,
        );
    }

    private function detectOrmRelationType(string $fieldClass, object $ormField): ?RelationType
    {
        if ($ormField instanceof ManyToMany || str_contains($fieldClass, 'ManyToMany')) {
            return RelationType::BELONGS_TO_MANY;
        }

        if ($ormField instanceof OneToMany || str_contains($fieldClass, 'OneToMany')) {
            return RelationType::HAS_MANY;
        }

        if ($ormField instanceof Reference || str_contains($fieldClass, 'Reference')) {
            if (method_exists($ormField, 'isOneToOne') && (bool) $ormField->isOneToOne()) {
                return RelationType::HAS_ONE;
            }

            return RelationType::BELONGS_TO;
        }

        return null;
    }

    /**
     * @return array{
     *     mediatorEntity: ?string,
     *     foreignKey: ?string,
     *     ownerKey: string,
     *     relatedKey: string,
     *     foreignPivotKey: ?string,
     *     relatedPivotKey: ?string
     * }
     */
    private function extractRelationKeys(object $ormField, string $fieldClass): array
    {
        $result = [
            'mediatorEntity' => null,
            'foreignKey' => null,
            'ownerKey' => 'ID',
            'relatedKey' => 'ID',
            'foreignPivotKey' => null,
            'relatedPivotKey' => null,
        ];

        if ($ormField instanceof Reference || str_contains($fieldClass, 'Reference')) {
            $elementals = method_exists($ormField, 'getElementals') ? $ormField->getElementals() : false;
            if (is_array($elementals) && $elementals !== []) {
                $localField = (string) array_key_first($elementals);
                $remoteField = (string) reset($elementals);
                $result['foreignKey'] = $localField;
                $result['relatedKey'] = $remoteField;
            }
        }

        if ($ormField instanceof OneToMany || str_contains($fieldClass, 'OneToMany')) {
            if (method_exists($ormField, 'getReferenceName')) {
                $result['foreignKey'] = (string) $ormField->getReferenceName();
            }
        }

        if ($ormField instanceof ManyToMany || str_contains($fieldClass, 'ManyToMany')) {
            if (method_exists($ormField, 'getMediatorEntity')) {
                $mediator = $ormField->getMediatorEntity();
                if (is_object($mediator) && method_exists($mediator, 'getDataClass')) {
                    $result['mediatorEntity'] = (string) $mediator->getDataClass();
                }
            }

            if (method_exists($ormField, 'getLocalReferenceName')) {
                $result['localMediatorReference'] = (string) $ormField->getLocalReferenceName();
            }

            if (method_exists($ormField, 'getRemoteReferenceName')) {
                $result['remoteMediatorReference'] = (string) $ormField->getRemoteReferenceName();
            }

            // Scalar pivot column names are not exposed by Bitrix ManyToMany; use foreignPivotKey() on the field DSL.
        }

        return $result;
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
