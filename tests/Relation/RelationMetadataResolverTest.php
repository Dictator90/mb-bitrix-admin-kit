<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\RelationMetadataResolver;
use PHPUnit\Framework\TestCase;

final class RelationMetadataResolverTest extends TestCase
{
    public function testExplicitPivotKeysWinOverOrmDetection(): void
    {
        $field = BelongsToMany::make('Sections', 'SECTIONS')
            ->relation('SECTIONS')
            ->relatedTable(MetadataResolverFakeRelatedTable::class)
            ->pivotTable(MetadataResolverFakePivotTable::class)
            ->mediatorReferences('IBLOCK_ELEMENT', 'IBLOCK_SECTION')
            ->foreignPivotKey('IBLOCK_ELEMENT_ID')
            ->relatedPivotKey('IBLOCK_SECTION_ID');

        $metadata = (new RelationMetadataResolver())->resolve(
            MetadataResolverFakeOwnerTable::class,
            $field,
            registerRuntime: true,
        );

        self::assertNotNull($metadata);
        self::assertSame('IBLOCK_ELEMENT_ID', $metadata->foreignPivotKey);
        self::assertSame('IBLOCK_SECTION_ID', $metadata->relatedPivotKey);
        self::assertSame('IBLOCK_ELEMENT', $metadata->localMediatorReference);
        self::assertSame('IBLOCK_SECTION', $metadata->remoteMediatorReference);
    }
}

/** @internal */
final class MetadataResolverFakeOwnerTable
{
    public static function getEntity(): MetadataResolverFakeEntity
    {
        return new MetadataResolverFakeEntity();
    }
}

/** @internal */
final class MetadataResolverFakeEntity
{
    private bool $hasSections = false;

    public function hasField(string $name): bool
    {
        return $name === 'SECTIONS' && $this->hasSections;
    }

    public function addField(object $field): void
    {
        $this->hasSections = true;
    }

    public function getField(string $name): object
    {
        return new MetadataResolverFakeManyToManyField();
    }
}

/** @internal */
final class MetadataResolverFakeManyToManyField
{
    public function getRefEntity(): MetadataResolverFakeEntity
    {
        return new MetadataResolverFakeEntity();
    }

    public function isMultiple(): bool
    {
        return true;
    }

    public function getMediatorEntity(): object
    {
        return new class {
            public function getDataClass(): string
            {
                return MetadataResolverFakePivotTable::class;
            }
        };
    }

    public function getLocalReferenceName(): string
    {
        return 'IBLOCK_ELEMENT';
    }

    public function getRemoteReferenceName(): string
    {
        return 'IBLOCK_SECTION';
    }
}

/** @internal */
final class MetadataResolverFakeRelatedTable
{
}

/** @internal */
final class MetadataResolverFakePivotTable
{
}
