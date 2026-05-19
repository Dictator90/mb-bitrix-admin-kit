<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use Bitrix\Main\ORM\Objectify\EntityObject;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

final class OrmObjectRelationSynchronizer implements RelationSynchronizerInterface
{
    public function __construct(private readonly RelationObjectMutator $mutator = new RelationObjectMutator())
    {
    }

    public function sync(EntityObject $owner, RelationField $field, RelationMetadata $metadata, mixed $value, DbOperationContext $context): void
    {
        $this->mutator->mutate($owner, $field, $metadata, $value, $context);
    }
}
