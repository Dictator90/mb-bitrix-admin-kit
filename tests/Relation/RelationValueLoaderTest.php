<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationType;
use MB\Bitrix\AdminKit\Relation\RelationValueLoader;
use PHPUnit\Framework\TestCase;

final class RelationValueLoaderTest extends TestCase
{
    public function testLoadsBelongsToScalarFromArray(): void
    {
        $field = BelongsTo::make('Category', 'CATEGORY_ID');
        $metadata = new RelationMetadata(
            relationType: RelationType::BELONGS_TO,
            ownerEntity: 'Owner',
            relatedEntity: 'Related',
            foreignKey: 'CATEGORY_ID',
            relationName: 'CATEGORY',
        );

        $value = (new RelationValueLoader())->load(['CATEGORY_ID' => '5'], $field, $metadata);

        self::assertSame('5', $value);
    }

    public function testLoadsBelongsToManyIdsFromCollection(): void
    {
        $field = BelongsToMany::make('Tags', 'TAGS')->relation('TAGS');
        $metadata = new RelationMetadata(
            relationType: RelationType::BELONGS_TO_MANY,
            ownerEntity: 'Owner',
            relatedEntity: 'Related',
            relationName: 'TAGS',
            multiple: true,
        );

        $collection = new FakeEntityCollection([
            new FakeSimpleEntityObject(['ID' => '1']),
            new FakeSimpleEntityObject(['ID' => '2']),
        ]);

        $value = (new RelationValueLoader())->load(['TAGS' => $collection], $field, $metadata);

        self::assertSame(['1', '2'], $value);
    }

    public function testLoadsLegacyCsvIds(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS', 'TagTable');
        $metadata = new RelationMetadata(
            relationType: RelationType::BELONGS_TO_MANY,
            ownerEntity: 'Owner',
            relatedEntity: 'TagTable',
            relationName: 'TAGS',
            multiple: true,
        );

        $value = (new RelationValueLoader())->load(['TAG_IDS' => '1,2,3'], $field, $metadata);

        self::assertSame(['1', '2', '3'], $value);
    }
}
