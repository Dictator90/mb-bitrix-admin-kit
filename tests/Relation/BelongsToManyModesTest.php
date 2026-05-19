<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use PHPUnit\Framework\TestCase;

final class BelongsToManyModesTest extends TestCase
{
    public function testLegacyCsvModeSerializesToString(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS', FakeTagTable::class);

        self::assertTrue($field->isStoredAsCsv());
        self::assertFalse($field->isOrmRelationMode());
        self::assertSame('1,2,3', $field->serializePostValue(['1', '2', '3']));
    }

    public function testOrmRelationModeViaRelationName(): void
    {
        $field = BelongsToMany::make('Tags', 'TAGS')
            ->relation('TAGS');

        self::assertTrue($field->isOrmRelationMode());
        self::assertFalse($field->isStoredAsCsv());
        self::assertSame(['1', '2', '3'], $field->serializePostValue(['1', '2', '3']));
    }

    public function testOrmRelationModeViaRelatedAndPivotTable(): void
    {
        $field = BelongsToMany::make('Tags', 'TAGS')
            ->relatedTable(FakeTagTable::class)
            ->pivotTable(FakePivotTable::class);

        self::assertTrue($field->isOrmRelationMode());
        self::assertSame(['5', '9'], $field->serializePostValue(['5', '9']));
    }

    public function testSaveUsingOrmEnablesOrmMode(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS', FakeTagTable::class)->saveUsingOrm();

        self::assertTrue($field->isOrmRelationMode());
    }

    public function testSaveUsingManualSyncEnablesOrmMode(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS', FakeTagTable::class)->saveUsingManualSync();

        self::assertTrue($field->isOrmRelationMode());
        self::assertSame('manual', $field->saveStrategy());
    }
}

final class FakeTagTable
{
}

final class FakePivotTable
{
}
