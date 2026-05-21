<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Relation\MediatorReferenceOrientation;
use PHPUnit\Framework\TestCase;

final class MediatorReferenceOrientationTest extends TestCase
{
    public function testKeepsUserOwnerOrder(): void
    {
        [$local, $remote] = MediatorReferenceOrientation::orient(
            OrientFakeUserGroupTable::class,
            OrientFakeUserTable::class,
            OrientFakeGroupTable::class,
            'USER',
            'GROUP',
        );

        self::assertSame(['USER', 'GROUP'], [$local, $remote]);
    }

    public function testSwapsReferencesForGroupOwner(): void
    {
        [$local, $remote] = MediatorReferenceOrientation::orient(
            OrientFakeUserGroupTable::class,
            OrientFakeGroupTable::class,
            OrientFakeUserTable::class,
            'USER',
            'GROUP',
        );

        self::assertSame(['GROUP', 'USER'], [$local, $remote]);
    }
}

final class OrientFakeUserTable
{
}

final class OrientFakeGroupTable
{
}

final class OrientFakeUserGroupTable
{
    public static function getEntity(): OrientFakeUserGroupEntity
    {
        return new OrientFakeUserGroupEntity();
    }
}

final class OrientFakeUserGroupEntity
{
    /** @var array<string, OrientFakeReferenceField> */
    private array $fields;

    public function __construct()
    {
        $this->fields = [
            'USER' => new OrientFakeReferenceField(OrientFakeUserTable::class),
            'GROUP' => new OrientFakeReferenceField(OrientFakeGroupTable::class),
        ];
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function getField(string $name): OrientFakeReferenceField
    {
        return $this->fields[$name];
    }
}

final class OrientFakeReferenceField
{
    public function __construct(private string $refDataClass)
    {
    }

    public function getRefEntity(): OrientFakeRefEntity
    {
        return new OrientFakeRefEntity($this->refDataClass);
    }
}

final class OrientFakeRefEntity
{
    public function __construct(private string $dataClass)
    {
    }

    public function getDataClass(): string
    {
        return $this->dataClass;
    }
}
