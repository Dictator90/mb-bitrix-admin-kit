<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Field\RelationField;

final class RuntimeRelationRegistrar
{
    public function register(string $ownerDataManagerClass, RelationField $field): void
    {
        if (!method_exists($ownerDataManagerClass, 'getEntity')) {
            return;
        }

        if (!$field->hasExplicitRelationDefinition()) {
            return;
        }

        $entity = $ownerDataManagerClass::getEntity();
        $relationName = $field->relationName() ?: $field->getColumn();
        if ($relationName === '' || !method_exists($entity, 'addField') || (method_exists($entity, 'hasField') && $entity->hasField($relationName))) {
            return;
        }

        // Runtime relation registration hook.
        // Concrete Bitrix relation field objects are created by dedicated builders/resolvers.
    }
}
