<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Resource\CrudResource;
use PHPUnit\Framework\TestCase;

final class CrudResourceDslTest extends TestCase
{
    public function testCrudResourceHasDslDefaults(): void
    {
        $resource = new DslTestResource();

        self::assertFalse($resource->hasCrud());
        self::assertEmpty(iterator_to_array($resource->indexFields()));
        self::assertEmpty(iterator_to_array($resource->formFields()));
        self::assertEmpty(iterator_to_array($resource->filters()));
        self::assertEmpty(iterator_to_array($resource->rowActions()));
        self::assertEmpty(iterator_to_array($resource->bulkActions()));
        self::assertFalse($resource->useSidePanel());
        self::assertSame(200, $resource->maxPageSize());
        self::assertTrue($resource->allowExportByFilter());
    }

    public function testCrudResourceHasActionsApi(): void
    {
        $resource = new DslTestResource();

        self::assertTrue($resource->hasAction('create'));
        self::assertTrue($resource->hasAction('view'));
        self::assertTrue($resource->hasAnyAction('update', 'delete'));
        self::assertFalse($resource->hasAction('non-existent'));
    }
}

final class DslTestResource extends CrudResource
{
}
