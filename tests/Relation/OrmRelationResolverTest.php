<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\OrmRelationResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OrmRelationResolverTest extends TestCase
{
    public function testResolvesReferenceRelation(): void
    {
        $field = BelongsTo::make('Category', 'CATEGORY');
        $metadata = (new OrmRelationResolver())->resolve(FakeOwnerTable::class, $field);

        self::assertNotNull($metadata);
        self::assertSame('CATEGORY', $metadata->relationName);
        self::assertSame(FakeRelatedTable::class, $metadata->relatedEntity);
    }

    public function testReturnsNullWhenRelationIsMissing(): void
    {
        $field = BelongsTo::make('Missing', 'UNKNOWN');
        $metadata = (new OrmRelationResolver())->resolve(FakeOwnerTable::class, $field);

        self::assertNull($metadata);
    }

    public function testThrowsOnWrongRelationType(): void
    {
        $this->expectException(RuntimeException::class);
        $field = BelongsToMany::make('Tags', 'CATEGORY');
        (new OrmRelationResolver())->resolve(FakeOwnerTable::class, $field);
    }
}

final class FakeOwnerTable
{
    public static function getEntity(): FakeEntity
    {
        return new FakeEntity();
    }
}

final class FakeRelatedTable
{
}

final class FakeEntity
{
    public function hasField(string $name): bool
    {
        return $name === 'CATEGORY';
    }

    public function getField(string $name): object
    {
        return new OrmResolverFakeReferenceField();
    }
}

final class OrmResolverFakeReferenceField
{
    public function getRefEntity(): object
    {
        return new class () {
            public function getDataClass(): string
            {
                return FakeRelatedTable::class;
            }
        };
    }

    public function isMultiple(): bool
    {
        return false;
    }
}
