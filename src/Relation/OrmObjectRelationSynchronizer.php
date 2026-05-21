<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\Strategies\ManualPivotSyncStrategy;
use MB\Bitrix\AdminKit\Relation\Strategies\OrmMutationSyncStrategy;
use MB\Bitrix\AdminKit\Relation\Strategies\RelationSyncStrategyInterface;

final class OrmObjectRelationSynchronizer implements RelationSynchronizerInterface
{
    /** @var list<RelationSyncStrategyInterface> */
    private array $strategies = [];

    public function __construct()
    {
        $this->strategies = [
            new ManualPivotSyncStrategy(),
            new OrmMutationSyncStrategy(),
        ];
    }

    public function registerStrategy(RelationSyncStrategyInterface $strategy): void
    {
        array_unshift($this->strategies, $strategy);
    }

    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context,
    ): void {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canSync($field, $metadata)) {
                $strategy->sync($owner, $field, $metadata, $value, $context);

                return;
            }
        }
    }
}
