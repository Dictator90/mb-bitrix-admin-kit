<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use InvalidArgumentException;
use MB\Bitrix\AdminKit\Field\RelationField;

final class RuntimeRelationBuilder
{
    public function build(RelationField $field): object
    {
        $metadata = $field->buildExplicitRelationMetadata('');

        return match ($field->relationType()) {
            RelationType::BELONGS_TO, RelationType::HAS_ONE => $this->buildReference($metadata),
            RelationType::HAS_MANY => $this->buildOneToMany($metadata),
            RelationType::BELONGS_TO_MANY => $this->buildManyToMany($metadata),
        };
    }

    private function buildReference(RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('Reference relation requires relatedTable() and foreignKey().');
        }

        return (object) ['type' => 'reference', 'metadata' => $metadata];
    }

    private function buildOneToMany(RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->foreignKey === null || $metadata->foreignKey === '') {
            throw new InvalidArgumentException('OneToMany relation requires relatedTable() and foreignKey().');
        }

        return (object) ['type' => 'one_to_many', 'metadata' => $metadata];
    }

    private function buildManyToMany(RelationMetadata $metadata): object
    {
        if ($metadata->relatedEntity === '' || $metadata->mediatorEntity === null || $metadata->mediatorEntity === '') {
            throw new InvalidArgumentException('ManyToMany relation requires relatedTable() and pivotTable().');
        }

        return (object) ['type' => 'many_to_many', 'metadata' => $metadata];
    }
}
