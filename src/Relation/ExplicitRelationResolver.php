<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Contracts\Relation\RelationResolverInterface;
use MB\Bitrix\AdminKit\Field\RelationField;

final class ExplicitRelationResolver implements RelationResolverInterface
{
    public function resolve(string $ownerDataManagerClass, RelationField $field): ?RelationMetadata
    {
        if (!$field->hasExplicitRelationDefinition()) {
            return null;
        }

        return $field->buildExplicitRelationMetadata($ownerDataManagerClass);
    }
}
