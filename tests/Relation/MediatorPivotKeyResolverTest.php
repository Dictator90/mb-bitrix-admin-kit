<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\MediatorPivotKeyResolver;
use MB\Bitrix\AdminKit\Relation\RelationMetadataResolver;
use PHPUnit\Framework\TestCase;

final class MediatorPivotKeyResolverTest extends TestCase
{
    public function testResolvesPivotColumnsFromMediatorReferences(): void
    {
        $resolved = MediatorPivotKeyResolver::resolve(
            FakeUserGroupPivotTable::class,
            'USER',
            'GROUP',
        );

        self::assertSame(['USER_ID', 'GROUP_ID'], $resolved);
    }

    public function testBelongsToManyPersistsViaPivotWhenOnlyMediatorReferencesConfigured(): void
    {
        $field = BelongsToMany::make('Groups', 'GROUPS')
            ->relatedTable(FakeGroupTable::class)
            ->pivotTable(FakeUserGroupPivotTable::class)
            ->mediatorReferences('USER', 'GROUP')
            ->saveUsingOrm();

        self::assertTrue($field->persistsViaPivotTable());
    }

    public function testMetadataResolverEnrichesPivotKeysFromMediatorReferences(): void
    {
        $field = BelongsToMany::make('Groups', 'GROUPS')
            ->relation('GROUPS')
            ->relatedTable(FakeGroupTable::class)
            ->pivotTable(FakeUserGroupPivotTable::class)
            ->mediatorReferences('USER', 'GROUP');

        $metadata = (new RelationMetadataResolver())->resolve(FakeUserTable::class, $field, false);

        self::assertNotNull($metadata);
        self::assertSame('USER_ID', $metadata->foreignPivotKey);
        self::assertSame('GROUP_ID', $metadata->relatedPivotKey);
    }

    public function testMetadataResolverReordersMediatorReferencesForGroupOwner(): void
    {
        $field = BelongsToMany::make('Users', 'GROUP_USERS')
            ->relation('GROUP_USERS')
            ->relatedTable(FakeUserTable::class)
            ->pivotTable(FakeUserGroupPivotTable::class)
            ->mediatorReferences('USER', 'GROUP');

        $metadata = (new RelationMetadataResolver())->resolve(FakeGroupTable::class, $field, false);

        self::assertNotNull($metadata);
        self::assertSame('GROUP', $metadata->localMediatorReference);
        self::assertSame('USER', $metadata->remoteMediatorReference);
        self::assertSame('GROUP_ID', $metadata->foreignPivotKey);
        self::assertSame('USER_ID', $metadata->relatedPivotKey);
    }
}

final class FakeUserTable
{
    public static function getEntity(): FakeUserGroupEntity
    {
        return new FakeUserGroupEntity();
    }
}

final class FakeGroupTable
{
    public static function getEntity(): FakeUserGroupEntity
    {
        return new FakeUserGroupEntity();
    }
}

/** @phpstan-ignore-next-line test fake */
final class FakeUserGroupPivotTable
{
    public static function getEntity(): FakeUserGroupEntity
    {
        return new FakeUserGroupEntity();
    }
}

final class FakeUserGroupEntity
{
    /** @var array<string, FakeReferenceField> */
    private array $fields;

    public function __construct()
    {
        $this->fields = [
            'USER' => new FakeReferenceField(['USER_ID' => 'ID'], FakeUserTable::class),
            'GROUP' => new FakeReferenceField(['GROUP_ID' => 'ID'], FakeGroupTable::class),
        ];
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function getField(string $name): FakeReferenceField
    {
        return $this->fields[$name];
    }
}

final class FakeReferenceField
{
    /** @param array<string, string> $elementals */
    public function __construct(private array $elementals, private string $refDataClass = '')
    {
    }

    /** @return array<string, string> */
    public function getElementals(): array
    {
        return $this->elementals;
    }

    public function getRefEntity(): FakeRefEntity
    {
        return new FakeRefEntity($this->refDataClass);
    }
}

final class FakeRefEntity
{
    public function __construct(private string $dataClass)
    {
    }

    public function getDataClass(): string
    {
        return $this->dataClass;
    }
}
