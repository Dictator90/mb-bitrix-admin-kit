<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use InvalidArgumentException;
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\HasMany;
use MB\Bitrix\AdminKit\Field\Relation\HasOne;
use MB\Bitrix\AdminKit\Relation\RuntimeRelationBuilder;
use PHPUnit\Framework\TestCase;

final class RuntimeRelationBuilderTest extends TestCase
{
    public function testBelongsToBuildsReference(): void
    {
        $field = BelongsTo::make('Category', 'CATEGORY_ID')
            ->relatedTable(BuilderFakeRelatedTable::class)
            ->foreignKey('CATEGORY_ID')
            ->relation('CATEGORY');

        $built = (new RuntimeRelationBuilder())->build($field);

        self::assertInstanceOf(Reference::class, $built);
    }

    public function testHasOneBuildsReverseReference(): void
    {
        $field = HasOne::make('Profile', 'PROFILE')
            ->relatedTable(BuilderFakeRelatedTable::class)
            ->foreignKey('USER_ID')
            ->relation('PROFILE');

        $built = (new RuntimeRelationBuilder())->build($field);

        self::assertInstanceOf(Reference::class, $built);
    }

    public function testHasManyBuildsOneToMany(): void
    {
        $field = HasMany::make('Items', 'ITEMS')
            ->relatedTable(BuilderFakeRelatedTable::class)
            ->foreignKey('ORDER')
            ->relation('ITEMS');

        $built = (new RuntimeRelationBuilder())->build($field);

        self::assertInstanceOf(OneToMany::class, $built);
    }

    public function testBelongsToManyBuildsManyToMany(): void
    {
        $field = BelongsToMany::make('Tags', 'TAGS')
            ->relatedTable(BuilderFakeRelatedTable::class)
            ->pivotTable(BuilderFakePivotTable::class)
            ->mediatorReferences('OWNER_REF', 'TAG_REF')
            ->foreignPivotKey('OWNER_ID')
            ->relatedPivotKey('TAG_ID')
            ->relation('TAGS');

        $built = (new RuntimeRelationBuilder())->build($field);

        self::assertInstanceOf(ManyToMany::class, $built);
    }

    public function testManyToManyRequiresMediatorReferenceNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $field = BelongsToMany::make('Tags', 'TAGS')
            ->relatedTable(BuilderFakeRelatedTable::class)
            ->pivotTable(BuilderFakePivotTable::class)
            ->relation('TAGS');

        (new RuntimeRelationBuilder())->build($field);
    }
}

final class BuilderFakeRelatedTable
{
}

final class BuilderFakePivotTable
{
}
