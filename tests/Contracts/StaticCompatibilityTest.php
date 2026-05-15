<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Contracts\ExportResourceContract;
use MB\Bitrix\AdminKit\Contracts\IndexResourceContract;
use MB\Bitrix\AdminKit\Contracts\OrmResourceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\ResourceBackedIndexPageDefinition;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class StaticCompatibilityTest extends TestCase
{
    public function testProductResourceSatisfiesNarrowContractsUsedByCore(): void
    {
        $resource = new ProductResource();

        self::assertInstanceOf(ResourceContract::class, $resource);
        self::assertInstanceOf(ExportResourceContract::class, $resource);
        self::assertInstanceOf(IndexResourceContract::class, $resource);
        self::assertInstanceOf(OrmResourceContract::class, $resource);
    }

    public function testGridAndExportAdaptersAcceptProductResource(): void
    {
        ProductTable::reset();
        $resource = new ProductResource();
        $context = GridContext::make($resource, null, ['limit' => 20, 'offset' => 0]);

        $params = (new GridQueryBuilder())->build($resource, $context);
        self::assertArrayHasKey('select', $params);

        $definition = new ResourceBackedIndexPageDefinition($resource);
        self::assertNotEmpty(iterator_to_array($definition->fields(), false));

        $exportContext = new ExportContext($resource, selectedIds: [1]);
        self::assertSame($resource, $exportContext->resource);
    }
}
