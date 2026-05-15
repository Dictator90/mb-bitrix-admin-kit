<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class CrudResourceTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testCrudMethodsUseDataManager(): void
    {
        $resource = new ProductResource();
        self::assertSame(['ID' => 1, 'NAME' => 'One'], $resource->findItem(1));
        self::assertSame(2, $resource->createItem(['NAME' => 'Two']));
        self::assertTrue($resource->updateItem(1, ['NAME' => 'New']));
        self::assertTrue($resource->deleteItem(1));
    }

    public function testCrudResourceInheritsGridAndExportDefaultsFromResource(): void
    {
        $resource = new ProductResource();

        self::assertTrue($resource->hasCrud());
        self::assertSame(ProductTable::class, $resource->dataManagerClass());
        self::assertSame(['ID' => 'ASC'], $resource->defaultSort());
        self::assertSame(200, $resource->maxPageSize());
        self::assertTrue($resource->allowExportByFilter());
        self::assertFalse($resource->allowExportAll());
        self::assertSame(5000, $resource->maxExportRows());
        self::assertSame(100, $resource->bulkChunkSize());
    }
}
