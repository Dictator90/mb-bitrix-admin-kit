<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use RuntimeException;
use Throwable;

final class OrmObjectRelationSynchronizer implements RelationSynchronizerInterface
{
    public function __construct(private readonly RelationObjectMutator $mutator = new RelationObjectMutator())
    {
    }

    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context,
    ): void {
        if ($field instanceof BelongsToMany && $field->saveStrategy() === 'manual') {
            (new ManualPivotSynchronizer())->sync($owner, $field, $metadata, $value, $context);

            return;
        }

        try {
            $this->mutator->mutate($owner, $field, $metadata, $value, $context);
        } catch (Throwable $exception) {
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
