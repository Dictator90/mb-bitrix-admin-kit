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
            RelationType::BELONGS_TO => $this->buildBelongsToReference($name, $metadata),
            RelationType::HAS_ONE => $this->buildHasOneReference($name, $metadata),
            RelationType::HAS_MANY => $this->buildOneToMany($name, $metadata),
            RelationType::BELONGS_TO_MANY => $this->buildManyToMany($name, $metadata),
        };
    }

    private function buildBelongsToReference(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('BelongsTo runtime relation requires relatedTable() and foreignKey().');
        }

        return $this->createReference(
            $name,
            $metadata->relatedEntity,
            'this.' . $metadata->foreignKey,
            'ref.' . $metadata->relatedKey,
        );
    }

    private function buildHasOneReference(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('HasOne runtime relation requires relatedTable() and foreignKey().');
        }

        return $this->createReference(
            $name,
            $metadata->relatedEntity,
            'this.' . $metadata->ownerKey,
            'ref.' . $metadata->foreignKey,
        );
    }

    private function buildOneToMany(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('HasMany runtime relation requires relatedTable() and foreignKey().');
        }

        if (!class_exists(OneToMany::class)) {
            throw new RuntimeException('Bitrix OneToMany relation class is not available in current core version.');
        }

        return new OneToMany($name, $metadata->relatedEntity, $metadata->foreignKey);
    }

    private function buildManyToMany(string $name, RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->mediatorEntity === null || $metadata->mediatorEntity === '') {
            throw new InvalidArgumentException('ManyToMany runtime relation requires relatedTable() and pivotTable().');
        }

        if (!class_exists(ManyToMany::class)) {
            throw new RuntimeException('Bitrix ManyToMany relation class is not available in current core version.');
        }

        $relation = new ManyToMany($name, $metadata->relatedEntity);
        $relation->configureMediatorEntity($metadata->mediatorEntity);

        if ($metadata->foreignPivotKey === null || $metadata->relatedPivotKey === null) {
            throw new RuntimeException('ManyToMany runtime relation requires foreignPivotKey() and relatedPivotKey().');
        }

        $relation->configureLocalPrimary($metadata->ownerKey, $metadata->foreignPivotKey);
        $relation->configureRemotePrimary($metadata->relatedKey, $metadata->relatedPivotKey);
        $relation->configureLocalReference($metadata->foreignPivotKey);
        $relation->configureRemoteReference($metadata->relatedPivotKey);

        return $relation;
    }

    private function createReference(string $name, string $relatedEntity, string $left, string $right): object
    {
        if (!class_exists(Reference::class)) {
            throw new RuntimeException('Bitrix Reference relation class is not available in current core version.');
        }

        if (class_exists('Bitrix\\Main\\ORM\\Query\\Join')) {
            return new Reference($name, $relatedEntity, \Bitrix\Main\ORM\Query\Join::on($left, $right));
        }

        return new Reference($name, $relatedEntity, ['=' . $left => $right]);
    }
}
