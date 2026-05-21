<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

final readonly class RelationMetadata
{
    public function __construct(
        public RelationType $relationType,
        public string $ownerEntity,
        public string $relatedEntity,
        public ?string $mediatorEntity = null,
        public ?string $foreignKey = null,
        public string $ownerKey = 'ID',
        public string $relatedKey = 'ID',
        public ?string $foreignPivotKey = null,
        public ?string $relatedPivotKey = null,
        public string $localMediatorReference = '',
        public string $remoteMediatorReference = '',
        public bool $multiple = false,
        public bool $cascadeSave = false,
        public bool $cascadeDelete = false,
        public bool $orphanRemoval = false,
        public string $relationName = '',
        public ?string $ormFieldClass = null,
        public ?RelationType $ormDetectedType = null,
    ) {
    }
}
