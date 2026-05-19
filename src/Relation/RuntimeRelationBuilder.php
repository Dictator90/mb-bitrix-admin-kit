<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use InvalidArgumentException;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use RuntimeException;

final class RuntimeRelationBuilder
{
    public function build(RelationField $field): object
    {
        $metadata = $field->buildExplicitRelationMetadata('');
        $name = $metadata->relationName !== '' ? $metadata->relationName : $field->getColumn();

        return match ($field->relationType()) {
            RelationType::BELONGS_TO, RelationType::HAS_ONE => $this->buildReference($name, $metadata),
            RelationType::HAS_MANY => $this->buildOneToMany($name, $metadata),
            RelationType::BELONGS_TO_MANY => $this->buildManyToMany($name, $metadata),
        };
    }

    private function buildReference(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('Reference relation requires relatedTable() and foreignKey().');
        }

        $reference = class_exists('Bitrix\\Main\\ORM\\Query\\Join')
            ? new Reference($name, $metadata->relatedEntity, \Bitrix\Main\ORM\Query\Join::on('this.' . $metadata->foreignKey, 'ref.' . $metadata->relatedKey))
            : new Reference($name, $metadata->relatedEntity, ['=this.' . $metadata->foreignKey => 'ref.' . $metadata->relatedKey]);

        return $reference;
    }

    private function buildOneToMany(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('OneToMany relation requires relatedTable() and foreignKey().');
        }

        if (!class_exists(OneToMany::class)) {
            throw new RuntimeException('Bitrix OneToMany relation class is not available in current core version.');
        }

        return new OneToMany($name, $metadata->relatedEntity, $metadata->foreignKey);
    }

    private function buildManyToMany(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->mediatorEntity === null || $metadata->mediatorEntity === '') {
            throw new InvalidArgumentException('ManyToMany relation requires relatedTable() and pivotTable().');
        }

        $relation = new ManyToMany($name, $metadata->relatedEntity);
        $relation->configureMediatorEntity($metadata->mediatorEntity);

        if ($metadata->foreignPivotKey === null || $metadata->relatedPivotKey === null) {
            throw new RuntimeException('ManyToMany runtime relation currently requires foreignPivotKey() and relatedPivotKey().');
        }

        $relation->configureLocalPrimary($metadata->ownerKey);
        $relation->configureRemotePrimary($metadata->relatedKey);
        $relation->configureLocalReference($metadata->foreignPivotKey);
        $relation->configureRemoteReference($metadata->relatedPivotKey);

        return $relation;
    }
}
