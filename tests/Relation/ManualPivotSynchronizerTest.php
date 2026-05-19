<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\ManualPivotSynchronizer;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationType;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ManualPivotSynchronizerTest extends TestCase
{
    protected function setUp(): void
    {
        FakePivotTableForSync::reset();
    }

    public function testSyncInsertsAndDeletesPivotRows(): void
    {
        FakePivotTableForSync::$rows = [
            ['OWNER_ID' => 1, 'TAG_ID' => '2'],
            ['OWNER_ID' => 1, 'TAG_ID' => '3'],
        ];

        (new ManualPivotSynchronizer())->syncPivotRows(FakePivotTableForSync::class, 'OWNER_ID', 1, 'TAG_ID', ['1', '3']);

        self::assertEqualsCanonicalizing(
            [['OWNER_ID' => 1, 'TAG_ID' => '1'], ['OWNER_ID' => 1, 'TAG_ID' => '3']],
            FakePivotTableForSync::$rows,
        );
    }

    public function testSyncThrowsWhenPivotMetadataIncomplete(): void
    {
        $this->expectException(RuntimeException::class);

        $owner = new MutatorFakeEntityObject(['ID' => 1]);

        $field = BelongsToMany::make('Tags', 'TAGS')->saveUsingManualSync()->relation('TAGS');
        $metadata = new RelationMetadata(
            relationType: RelationType::BELONGS_TO_MANY,
            ownerEntity: 'Owner',
            relatedEntity: 'Related',
            relationName: 'TAGS',
            multiple: true,
        );

        (new ManualPivotSynchronizer())->sync($owner, $field, $metadata, ['1'], new DbOperationContext(new ProductResource(), 'update'));
    }
}
