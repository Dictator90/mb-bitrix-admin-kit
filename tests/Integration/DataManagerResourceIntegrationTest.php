<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Integration;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use PHPUnit\Framework\TestCase;

// Define test table class
class TestBookTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_test_book';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new StringField('TITLE'))
                ->configureRequired(true),
            (new StringField('AUTHOR')),
        ];
    }
}

// Define test resource class
class TestBookResource extends DataManagerResource
{
    public static function getId(): string
    {
        return 'test_book';
    }

    public function dataManagerClass(): string
    {
        return TestBookTable::class;
    }

    public function getTitle(): string
    {
        return 'Books';
    }

    public function fields(): array
    {
        return [
            Text::make('Title', 'TITLE')->required(),
            Text::make('Author', 'AUTHOR'),
        ];
    }
}

final class DataManagerResourceIntegrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->queryExecute('DROP TABLE IF EXISTS b_test_book');
        $connection->queryExecute('
            CREATE TABLE b_test_book (
                ID INTEGER PRIMARY KEY AUTOINCREMENT,
                TITLE VARCHAR(255) NOT NULL,
                AUTHOR VARCHAR(255)
            )
        ');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->queryExecute('DELETE FROM b_test_book');
    }

    public function testCreateItemSucceedsAndPersistsToDatabase(): void
    {
        $resource = new TestBookResource();

        $id = $resource->createItem([
            'TITLE' => 'The Hobbit',
            'AUTHOR' => 'J.R.R. Tolkien',
        ]);

        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);

        // Fetch using standard Bitrix D7 ORM
        $row = TestBookTable::getByPrimary($id)->fetch();
        $this->assertNotFalse($row);
        $this->assertSame('The Hobbit', $row['TITLE']);
        $this->assertSame('J.R.R. Tolkien', $row['AUTHOR']);
    }

    public function testFindAndListItems(): void
    {
        $resource = new TestBookResource();

        $id1 = $resource->createItem(['TITLE' => 'Book One', 'AUTHOR' => 'Author A']);
        $id2 = $resource->createItem(['TITLE' => 'Book Two', 'AUTHOR' => 'Author B']);

        $item = $resource->findItem($id1);
        $this->assertNotNull($item);
        $this->assertSame('Book One', $item['TITLE']);

        $list = $resource->getList([
            'order' => ['ID' => 'ASC']
        ]);
        $this->assertCount(2, $list);
        $this->assertSame('Book One', $list[0]['TITLE']);
        $this->assertSame('Book Two', $list[1]['TITLE']);

        $this->assertSame(2, $resource->getCount());
    }

    public function testUpdateItemModifiesDatabaseRecord(): void
    {
        $resource = new TestBookResource();
        $id = $resource->createItem(['TITLE' => 'Original Title', 'AUTHOR' => 'Original Author']);

        $updated = $resource->updateItem($id, [
            'TITLE' => 'New Title',
            'AUTHOR' => 'New Author',
        ]);

        $this->assertTrue($updated);

        $row = TestBookTable::getByPrimary($id)->fetch();
        $this->assertSame('New Title', $row['TITLE']);
        $this->assertSame('New Author', $row['AUTHOR']);
    }

    public function testDeleteItemRemovesFromDatabase(): void
    {
        $resource = new TestBookResource();
        $id = $resource->createItem(['TITLE' => 'To Be Deleted', 'AUTHOR' => 'No Author']);

        $this->assertSame(1, $resource->getCount());

        $deleted = $resource->deleteItem($id);
        $this->assertTrue($deleted);

        $this->assertSame(0, $resource->getCount());
        $this->assertFalse(TestBookTable::getByPrimary($id)->fetch());
    }
}
