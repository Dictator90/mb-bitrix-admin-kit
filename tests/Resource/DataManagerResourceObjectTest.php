<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use LogicException;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductOrmEntityObject;
use PHPUnit\Framework\TestCase;

final class DataManagerResourceObjectTest extends TestCase
{
    public function testQueryObjectFindObjectAndNewObject(): void
    {
        ObjectQueryProductTable::$rows = [['ID' => 1, 'NAME' => 'One']];
        $resource = new ObjectQueryTestResource();

        $query = $resource->queryObject(['ID', 'NAME']);
        self::assertInstanceOf(ObjectQueryFake::class, $query);

        $object = $resource->findObject(1, ['*', 'TAGS']);
        self::assertInstanceOf(ProductOrmEntityObject::class, $object);
        self::assertSame(1, $object->getId());

        $new = $resource->newObject();
        self::assertInstanceOf(ProductOrmEntityObject::class, $new);
    }

    public function testGetEntityReturnsDataManagerEntity(): void
    {
        $resource = new ObjectQueryTestResource();

        self::assertInstanceOf(ObjectQueryEntityFake::class, $resource->getEntity());
    }

    public function testCompositePrimaryThrows(): void
    {
        $resource = new CompositePrimaryResource();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('composite primary keys');

        $resource->findObject(1);
    }

    public function testEmptyDataManagerClassThrows(): void
    {
        $resource = new class () extends DataManagerResource {
            public function dataManagerClass(): string
            {
                return '';
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must declare a non-empty dataManagerClass()');
        $resource->getDataManagerClass();
    }

    public function testNoGetEntityMethodThrows(): void
    {
        $resource = new class () extends DataManagerResource {
            public function dataManagerClass(): string
            {
                return MissingGetEntityTable::class;
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must implement getEntity()');
        $resource->getEntity();
    }
}

final class ObjectQueryTestResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ObjectQueryProductTable::class;
    }
}

final class CompositePrimaryResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return CompositePrimaryTable::class;
    }
}

final class ObjectQueryProductTable
{
    /** @var list<array<string,mixed>> */
    public static array $rows = [['ID' => 1, 'NAME' => 'One']];

    public static function query(): ObjectQueryFake
    {
        return new ObjectQueryFake(self::$rows);
    }

    public static function createObject(): ProductOrmEntityObject
    {
        return new ProductOrmEntityObject(['ID' => null, 'NAME' => '']);
    }

    public static function getEntity(): ObjectQueryEntityFake
    {
        return new ObjectQueryEntityFake();
    }
}

final class ObjectQueryEntityFake
{
    /** @return list<string> */
    public function getPrimaryArray(): array
    {
        return ['ID'];
    }
}

final class CompositePrimaryTable
{
    public static function query(): ObjectQueryFake
    {
        return new ObjectQueryFake([]);
    }

    public static function getEntity(): CompositePrimaryEntityFake
    {
        return new CompositePrimaryEntityFake(singlePrimary: false);
    }
}

final class ObjectQueryFake
{
    /** @var list<string> */
    private array $select = ['*'];

    private ?string $whereField = null;

    private mixed $whereValue = null;

    /** @param list<array<string,mixed>> $rows */
    public function __construct(private array $rows)
    {
    }

    /** @param list<string> $select */
    public function setSelect(array $select): self
    {
        $this->select = $select;

        return $this;
    }

    public function where(string $field, mixed $value): self
    {
        $this->whereField = $field;
        $this->whereValue = $value;

        return $this;
    }

    public function fetchObject(): ?ProductOrmEntityObject
    {
        if ($this->whereField === null) {
            return null;
        }

        foreach ($this->rows as $row) {
            if ((string) ($row[$this->whereField] ?? '') === (string) $this->whereValue) {
                return new ProductOrmEntityObject($row, false);
            }
        }

        return null;
    }
}

final class CompositePrimaryEntityFake
{
    public function __construct(private bool $singlePrimary)
    {
    }

    /** @return list<string> */
    public function getPrimaryArray(): array
    {
        return $this->singlePrimary ? ['ID'] : ['ID', 'SITE_ID'];
    }
}

final class MissingGetEntityTable
{
    // does not implement getEntity()
}
