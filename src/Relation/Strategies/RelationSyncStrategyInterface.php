<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation\Strategies;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;

interface RelationSyncStrategyInterface
{
    public function canSync(RelationField $field, RelationMetadata $metadata): bool;

    /**
     * @param EntityObject|object $owner Bitrix EntityObject or compatible test double with get/set/getId.
     */
    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context
    ): void;
}
