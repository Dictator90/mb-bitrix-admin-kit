<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

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

        return $this->ormResolver->resolve($ownerDataManagerClass, $field)
            ?? $this->explicitResolver->resolve($ownerDataManagerClass, $field);
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
            if ($metadata !== null && $metadata->relationName !== '') {
                $select[] = $metadata->relationName;
            }
        }

        return array_values(array_unique($select));
    }
}
