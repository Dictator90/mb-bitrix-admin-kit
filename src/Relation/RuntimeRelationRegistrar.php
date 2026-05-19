<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class RuntimeRelationRegistrar
{
    public function __construct(private readonly RuntimeRelationBuilder $builder = new RuntimeRelationBuilder())
    {
    }

    public function register(string $ownerDataManagerClass, RelationField $field): bool
    {
        if (!method_exists($ownerDataManagerClass, 'getEntity')) {
            return false;
        }

        if (!$field->hasExplicitRelationDefinition()) {
            return false;
        }

        $entity = $ownerDataManagerClass::getEntity();
        $relationName = $field->relationName() ?: $field->getColumn();
        if ($relationName === '' || !method_exists($entity, 'addField')) {
            return false;
        }

        if (method_exists($entity, 'hasField') && $entity->hasField($relationName)) {
            return false;
        }

        $entity->addField($this->builder->build($field));

        return true;
    }
}
