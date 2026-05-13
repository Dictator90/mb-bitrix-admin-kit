<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class CrudResourceTest extends TestCase
{
    public function testCrudMethodsUseDataManager(): void
    {
        $resource = new ProductResource();
        self::assertSame(['ID' => 1, 'NAME' => 'One'], $resource->findItem(1));
        self::assertSame(2, $resource->createItem(['NAME' => 'Two']));
        self::assertTrue($resource->updateItem(1, ['NAME' => 'New']));
        self::assertTrue($resource->deleteItem(1));
    }
}
