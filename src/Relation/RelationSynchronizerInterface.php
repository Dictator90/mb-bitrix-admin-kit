<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

interface RelationSynchronizerInterface
{
    /**
     * @param EntityObject|object $owner Bitrix EntityObject or compatible test double with get/set/getId.
     */
    public function sync(object $owner, RelationField $field, RelationMetadata $metadata, mixed $value, DbOperationContext $context): void;
}
