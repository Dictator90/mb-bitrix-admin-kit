<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation\Strategies;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\ManualPivotSynchronizer;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationObjectMutator;
use RuntimeException;
use Throwable;

final class OrmMutationSyncStrategy implements RelationSyncStrategyInterface
{
    public function __construct(private readonly RelationObjectMutator $mutator = new RelationObjectMutator())
    {
    }

    public function canSync(RelationField $field, RelationMetadata $metadata): bool
    {
        return true;
    }

    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context
    ): void {
        try {
            $this->mutator->mutate($owner, $field, $metadata, $value, $context);
        } catch (Throwable $exception) {
            if (
                $field instanceof BelongsToMany
                && !$field->persistsViaPivotTable($metadata)
                && $metadata->mediatorEntity !== null
                && $metadata->mediatorEntity !== ''
                && $metadata->foreignPivotKey !== null
                && $metadata->foreignPivotKey !== ''
                && $metadata->relatedPivotKey !== null
                && $metadata->relatedPivotKey !== ''
            ) {
                (new ManualPivotSynchronizer())->sync($owner, $field, $metadata, $value, $context);

                return;
            }

            if ($field instanceof BelongsToMany && $field->saveStrategy() === 'orm' && $metadata->mediatorEntity !== null) {
                throw new RuntimeException(
                    'BelongsToMany ORM sync failed: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception,
                );
            }

            throw $exception;
        }
    }
}
