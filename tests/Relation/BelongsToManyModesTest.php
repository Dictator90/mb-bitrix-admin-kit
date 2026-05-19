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

    public function testOrmRelationModeNormalizePreservesIdList(): void
    {
        $field = BelongsToMany::make('Groups', 'GROUPS')
            ->relatedTable(FakeTagTable::class)
            ->pivotTable(FakePivotTable::class)
            ->mediatorReferences('USER', 'GROUP');

        self::assertSame([1, 2], $field->normalize(['1', '2']));
        self::assertSame([5], $field->normalize(['5']));
        self::assertSame([], $field->normalize(null));
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

    public function testExplicitManyToManyPersistsViaPivotTable(): void
    {
        $field = BelongsToMany::make('Sections', 'SECTIONS')
            ->relatedTable(FakeTagTable::class)
            ->pivotTable(FakePivotTable::class)
            ->foreignPivotKey('OWNER_ID')
            ->relatedPivotKey('TAG_ID')
            ->saveUsingOrm();

        self::assertTrue($field->isOrmRelationMode());
        self::assertTrue($field->persistsViaPivotTable());
    }

    public function testSaveUsingManualSyncEnablesOrmMode(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS', FakeTagTable::class)->saveUsingManualSync();

        self::assertTrue($field->isOrmRelationMode());
        self::assertSame('manual', $field->saveStrategy());
    }

    public function testRelatedTableLoadsSelectOptions(): void
    {
        $field = BelongsToMany::make('Sections', 'SECTIONS')
            ->relatedTable(OptionsListFakeTable::class)
            ->titleColumn('NAME');

        $html = $field->renderFormField(['1']);

        self::assertStringContainsString('Section A', $html);
        self::assertStringContainsString('value="1" selected', $html);
        self::assertStringNotContainsString('value="2" selected', $html);
    }
}

final class FakeTagTable
{
}

final class FakePivotTable
{
}

final class OptionsListFakeTable
{
    /** @param array<string,mixed> $params */
    public static function getList(array $params): OptionsListFakeResult
    {
        return new OptionsListFakeResult([
            ['ID' => 1, 'NAME' => 'Section A'],
            ['ID' => 2, 'NAME' => 'Section B'],
        ]);
    }
}

final class OptionsListFakeResult
{
    /** @param list<array<string,mixed>> $rows */
    public function __construct(private array $rows)
    {
    }

    /** @return array<string,mixed>|false */
    public function fetch(): array|false
    {
        $row = array_shift($this->rows);

        return $row ?? false;
    }
}
