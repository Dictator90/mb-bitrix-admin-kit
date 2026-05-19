<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Objectify\EntityObject;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class RelationObjectMutator
{
    public function mutate(EntityObject $owner, RelationField $field, RelationMetadata $metadata, mixed $value, DbOperationContext $context): void
    {
        $target = $metadata->relationName !== '' ? $metadata->relationName : $field->getColumn();
        $owner->set($target, $value);
    }
}
