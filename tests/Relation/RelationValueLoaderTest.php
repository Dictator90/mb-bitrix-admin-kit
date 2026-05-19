<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\HasMany;
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

    public function testLoadsHasManyFromEntityObjectArray(): void
    {
        $field = HasMany::make('Links', 'USER_GROUP')->relation('USER_GROUP');
        $metadata = new RelationMetadata(
            relationType: RelationType::HAS_MANY,
            ownerEntity: 'Owner',
            relatedEntity: 'UserGroup',
            relationName: 'USER_GROUP',
            multiple: true,
        );

        $value = (new RelationValueLoader())->load(
            [
                'USER_GROUP' => [
                    new FakeUserGroupEntityObject(['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5]),
                    new FakeUserGroupEntityObject(['ID' => 2, 'USER_ID' => 12, 'GROUP_ID' => 5]),
                ],
            ],
            $field,
            $metadata,
        );

        self::assertCount(2, $value);
        self::assertSame(1, $value[0]['ID']);
        self::assertSame(11, $value[0]['USER_ID']);
        self::assertSame(12, $value[1]['USER_ID']);
    }

    public function testLoadsHasManyFromRelatedTableWhenOrmReturnsSingleRow(): void
    {
        FakeUserGroupTableForLoader::$rows = [
            ['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5],
            ['ID' => 2, 'USER_ID' => 12, 'GROUP_ID' => 5],
            ['ID' => 3, 'USER_ID' => 13, 'GROUP_ID' => 5],
        ];

        $field = HasMany::make('Links', 'USER_GROUP_TABLE')
            ->relation('USER_GROUP')
            ->relatedTable(FakeUserGroupTableForLoader::class)
            ->foreignKey('GROUP_ID')
            ->localKey('ID');
        $metadata = new RelationMetadata(
            relationType: RelationType::HAS_MANY,
            ownerEntity: 'Group',
            relatedEntity: FakeUserGroupTableForLoader::class,
            foreignKey: 'GROUP_ID',
            ownerKey: 'ID',
            relationName: 'USER_GROUP',
            multiple: true,
        );

        $value = (new RelationValueLoader())->load(
            [
                'ID' => 5,
                'USER_GROUP' => new FakeUserGroupEntityObject(['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5]),
            ],
            $field,
            $metadata,
        );

        self::assertCount(3, $value);
        self::assertSame(11, $value[0]['USER_ID']);
        self::assertSame(13, $value[2]['USER_ID']);

        FakeUserGroupTableForLoader::$rows = [];
    }
}

final class FakeUserGroupTableForLoader
{
    /** @var list<array<string, mixed>> */
    public static array $rows = [];

    public static function getList(array $params): object
    {
        $groupId = $params['filter']['GROUP_ID'] ?? null;
        $rows = array_values(array_filter(
            self::$rows,
            static fn (array $row): bool => (string) $row['GROUP_ID'] === (string) $groupId,
        ));

        return new class ($rows) {
            private int $i = 0;

            /** @param list<array<string, mixed>> $rows */
            public function __construct(private array $rows)
            {
            }

            public function fetch(): array|false
            {
                return $this->rows[$this->i++] ?? false;
            }
        };
    }
}

final class FakeUserGroupEntityObject
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values)
    {
    }

    public function get(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }

    public function getId(): mixed
    {
        return $this->values['ID'] ?? null;
    }

    public function getEntity(): FakeUserGroupEntity
    {
        return new FakeUserGroupEntity();
    }
}

final class FakeUserGroupEntity
{
    /** @return list<FakeUserGroupField>
     */
    public function getFields(): array
    {
        return [
            new FakeUserGroupField('ID'),
            new FakeUserGroupField('USER_ID'),
            new FakeUserGroupField('GROUP_ID'),
        ];
    }
}

final class FakeUserGroupField
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
