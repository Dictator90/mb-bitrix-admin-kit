<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Relation\ManualPivotSynchronizer;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationObjectMutator;
use MB\Bitrix\AdminKit\Relation\RelationType;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class RelationObjectMutatorTest extends TestCase
{
    public function testBelongsToSetsForeignKey(): void
    {
        $owner = new MutatorFakeEntityObject(['ID' => 1]);
        $field = BelongsTo::make('Category', 'CATEGORY_ID')->relation('CATEGORY');
        $metadata = new RelationMetadata(
            relationType: RelationType::BELONGS_TO,
            ownerEntity: 'Owner',
            relatedEntity: 'Related',
            foreignKey: 'CATEGORY_ID',
            relationName: 'CATEGORY',
        );

        (new RelationObjectMutator(new ManualPivotSynchronizer()))->mutate(
            $owner,
            $field,
            $metadata,
            '9',
            new DbOperationContext(new ProductResource(), 'update'),
        );

        self::assertSame('9', $owner->get('CATEGORY_ID'));
    }
}
