<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Integration;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use PHPUnit\Framework\TestCase;

// Define TestPostTable
class TestPostTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_test_post';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new StringField('TITLE'))
                ->configureRequired(true),
        ];
    }
}

// Define TestTagTable
class TestTagTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_test_tag';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new StringField('NAME'))
                ->configureRequired(true),
        ];
    }
}

// Define TestPostTagTable
class TestPostTagTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_test_post_tag';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('POST_ID'))
                ->configurePrimary(true),
            (new IntegerField('TAG_ID'))
                ->configurePrimary(true),
        ];
    }
}

// Define TestPostResource
class TestPostResource extends DataManagerResource
{
    public static function getId(): string
    {
        return 'test_post';
    }

    public function dataManagerClass(): string
    {
        return TestPostTable::class;
    }

    public function getTitle(): string
    {
        return 'Posts';
    }

    public function fields(): array
    {
        return [
            Text::make('Title', 'TITLE')->required(),
            BelongsToMany::make('Tags', 'TAGS')
                ->pivotTable(TestPostTagTable::class)
                ->foreignPivotKey('POST_ID')
                ->relatedPivotKey('TAG_ID'),
        ];
    }
}

final class RelationSyncIntegrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $connection = \Bitrix\Main\Application::getConnection();

        $connection->queryExecute('DROP TABLE IF EXISTS b_test_post');
        $connection->queryExecute('DROP TABLE IF EXISTS b_test_tag');
        $connection->queryExecute('DROP TABLE IF EXISTS b_test_post_tag');

        $connection->queryExecute('
            CREATE TABLE b_test_post (
                ID INTEGER PRIMARY KEY AUTOINCREMENT,
                TITLE VARCHAR(255) NOT NULL
            )
        ');

        $connection->queryExecute('
            CREATE TABLE b_test_tag (
                ID INTEGER PRIMARY KEY AUTOINCREMENT,
                NAME VARCHAR(255) NOT NULL
            )
        ');

        $connection->queryExecute('
            CREATE TABLE b_test_post_tag (
                POST_ID INTEGER NOT NULL,
                TAG_ID INTEGER NOT NULL,
                PRIMARY KEY (POST_ID, TAG_ID)
            )
        ');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->queryExecute('DELETE FROM b_test_post');
        $connection->queryExecute('DELETE FROM b_test_tag');
        $connection->queryExecute('DELETE FROM b_test_post_tag');
    }

    public function testBelongsToManySyncsRelationCorrectly(): void
    {
        // 1. Create tags
        $tagId1 = (int) TestTagTable::add(['NAME' => 'PHP'])->getId();
        $tagId2 = (int) TestTagTable::add(['NAME' => 'Bitrix'])->getId();
        $tagId3 = (int) TestTagTable::add(['NAME' => 'SQLite'])->getId();

        $resource = new TestPostResource();

        // 2. Create post with initial tags (1 and 2)
        $postId = $resource->createItem([
            'TITLE' => 'My First Post',
            'TAGS' => [$tagId1, $tagId2],
        ]);

        $this->assertIsNumeric($postId);

        // Verify tags are synced in pivot table
        $pivotRows = TestPostTagTable::getList([
            'filter' => ['POST_ID' => $postId],
            'order' => ['TAG_ID' => 'ASC'],
        ])->fetchAll();

        $this->assertCount(2, $pivotRows);
        $this->assertEquals($tagId1, $pivotRows[0]['TAG_ID']);
        $this->assertEquals($tagId2, $pivotRows[1]['TAG_ID']);

        // 3. Update post with different tags (2 and 3)
        $resource->updateItem($postId, [
            'TITLE' => 'My Updated Post',
            'TAGS' => [$tagId2, $tagId3],
        ]);

        // Verify updated tags in pivot table
        $pivotRows = TestPostTagTable::getList([
            'filter' => ['POST_ID' => $postId],
            'order' => ['TAG_ID' => 'ASC'],
        ])->fetchAll();

        $this->assertCount(2, $pivotRows);
        $this->assertEquals($tagId2, $pivotRows[0]['TAG_ID']);
        $this->assertEquals($tagId3, $pivotRows[1]['TAG_ID']);

        // 4. Update post with empty tags (clearing relation)
        $resource->updateItem($postId, [
            'TITLE' => 'My Post without Tags',
            'TAGS' => [],
        ]);

        // Verify no tags remain in pivot table
        $pivotRows = TestPostTagTable::getList([
            'filter' => ['POST_ID' => $postId],
        ])->fetchAll();

        $this->assertEmpty($pivotRows);
    }
}
