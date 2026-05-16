<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use LogicException;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class DataManagerResourceTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testDataManagerResourceRequiresClass(): void
    {
        $resource = new class extends DataManagerResource {
            public function dataManagerClass(): string { return ''; }
        };

        $this->expectException(LogicException::class);
        $resource->getDataManagerClass();
    }

    public function testDataManagerResourceHasPersistence(): void
    {
        $resource = new OrmTestResource();

        self::assertTrue($resource->hasCrud());
        self::assertSame(ProductTable::class, $resource->dataManagerClass());
        self::assertSame(['ID' => 1, 'NAME' => 'One'], $resource->findItem(1));
        
        $id = $resource->createItem(['NAME' => 'New Item']);
        self::assertSame(2, $id);
        self::assertSame(['ID' => 2, 'NAME' => 'New Item'], $resource->findItem(2));
    }
}

final class OrmTestResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }
}
