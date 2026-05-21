<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation\Strategies;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\ManualPivotSynchronizer;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;

final class ManualPivotSyncStrategy implements RelationSyncStrategyInterface
{
    public function canSync(RelationField $field, RelationMetadata $metadata): bool
    {
        return $field instanceof BelongsToMany
            && ($field->saveStrategy() === 'manual' || $field->persistsViaPivotTable($metadata));
    }

    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context
    ): void {
        (new ManualPivotSynchronizer())->sync($owner, $field, $metadata, $value, $context);
    }
}
