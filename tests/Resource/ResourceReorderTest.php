<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ResourceReorderTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testReorderWritesIncrementalSortViaDataManager(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }
        };

        $resource->reorder([3, 1, 2]);

        self::assertSame([3, 1, 2], ProductTable::$updatedIds);
        self::assertSame(['id' => 2, 'data' => ['SORT' => 300]], ProductTable::$lastUpdated);
    }

    public function testReorderUsesCustomSortStep(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }

            public function sortStep(): int
            {
                return 10;
            }
        };

        $resource->reorder([1, 2, 3]);

        // шаг 10 → 10, 20, 30
        self::assertSame(['id' => 3, 'data' => ['SORT' => 30]], ProductTable::$lastUpdated);
    }

    public function testReorderWritesGroupFieldOnCrossGroupMove(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }
        };

        // item 1 и 2 перенесены в группу '5' (TYPE_ID), порядок 1,2
        $resource->reorder(['1', '2'], ['1' => '5', '2' => '5'], 'TYPE_ID');

        // DataManager updates use the canonical integer primary key, while the
        // group value remains the value supplied by the grouped-grid payload.
        self::assertSame([1, 2], ProductTable::$updatedIds);
        self::assertSame(['id' => 2, 'data' => ['SORT' => 200, 'TYPE_ID' => '5']], ProductTable::$lastUpdated);
    }

    public function testReorderPreservesIntegerGroupValue(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }
        };

        $resource->reorder([1], ['1' => 5], 'TYPE_ID');

        self::assertSame(['id' => 1, 'data' => ['SORT' => 100, 'TYPE_ID' => 5]], ProductTable::$lastUpdated);
    }

    public function testReorderIgnoresGroupWhenFieldNull(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }
        };

        $resource->reorder(['1', '2'], ['1' => '5', '2' => '5'], null);

        self::assertSame(['id' => 2, 'data' => ['SORT' => 200]], ProductTable::$lastUpdated);
    }

    public function testReorderNoopWhenSortFieldNull(): void
    {
        $resource = new ProductResource(); // sortField() === null by default

        $resource->reorder([1, 2, 3]);

        self::assertSame([], ProductTable::$updatedIds);
    }

    public function testReorderNoopWhenEmpty(): void
    {
        $resource = new class () extends ProductResource {
            public function sortField(): ?string
            {
                return 'SORT';
            }
        };

        $resource->reorder([]);

        self::assertSame([], ProductTable::$updatedIds);
    }
}
