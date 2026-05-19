<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use MB\Bitrix\AdminKit\Support\AdminString;
use PHPUnit\Framework\TestCase;

final class GridDataLoaderCountRuntimeTest extends TestCase
{
    public function testCountWithRuntimeUsesQueryInsteadOfGetCount(): void
    {
        MockDataManager::$runtimeCountCalls = 0;
        $resource = new class () extends DataManagerResource {
            public static function getId(): string
            {
                return 'test_runtime_count';
            }
            public function dataManagerClass(): string
            {
                return MockDataManager::class;
            }
            public function getPrimaryKey(): string
            {
                return 'ID';
            }
            public function useTotalCount(\MB\Bitrix\AdminKit\Grid\GridContext $ctx): bool
            {
                return true;
            }
            public function countCacheTtl(\MB\Bitrix\AdminKit\Grid\GridContext $ctx): int
            {
                return 0;
            }
        };

        $grid = new Grid('test_runtime_count');
        $loader = new GridDataLoader();
        $definition = $this->makeDefinition([
            'indexRuntime' => fn () => ['PROPERTY' => 'runtime-field'],
            'indexFilter' => fn () => ['=PROPERTY.IBLOCK_ID' => 8],
        ]);

        $loader->load($resource, $grid, null, null, $definition);

        self::assertGreaterThan(0, MockDataManager::$runtimeCountCalls);
        self::assertSame(7, $grid->getTotalCount());
    }

    public function testCountCacheKeyIncludesRuntime(): void
    {
        ArrayTtlCache::clear();
        MockDataManager::$runtimeCountCalls = 0;
        $resource = new class () extends DataManagerResource {
            public static function getId(): string
            {
                return 'test';
            }
            public function dataManagerClass(): string
            {
                return MockDataManager::class;
            }
            public function getPrimaryKey(): string
            {
                return 'ID';
            }
            public function useTotalCount(\MB\Bitrix\AdminKit\Grid\GridContext $ctx): bool
            {
                return true;
            }
            public function countCacheTtl(\MB\Bitrix\AdminKit\Grid\GridContext $ctx): int
            {
                return 60;
            }
        };

        $grid = new Grid('test');
        $loader = new GridDataLoader();

        $def1 = $this->makeDefinition([
            'indexRuntime' => fn () => ['F1' => 'V1'],
        ]);

        $ctx = $loader->makeContext($resource, $grid);
        $params = (new GridQueryBuilder())->build($resource, $ctx, $def1);

        // Key building must match GridDataLoader::resolveTotalCount EXACTLY
        $key1 = AdminString::cacheKey('adminkit_count', [
            'module' => 'mb.bitrix.adminkit',
            'resource' => $resource::getId(),
            'grid' => $ctx->gridId,
            'filter' => $params['filter'],
            'runtime' => $params['runtime'],
            'user' => null,
        ]);

        ArrayTtlCache::set($key1, 42, 60);

        $loader->load($resource, $grid, null, $ctx, $def1);

        self::assertEquals(42, $grid->getTotalCount());

        // Runtime 2 - should NOT use cache from Runtime 1
        $def2 = $this->makeDefinition([
            'indexRuntime' => fn () => ['F2' => 'V2'],
        ]);

        $loader->load($resource, $grid, null, null, $def2);

        self::assertEquals(7, $grid->getTotalCount());
    }

    private function makeDefinition(array $overrides): IndexPageDefinition
    {
        $defaults = [
            'fields' => fn () => [],
            'filters' => fn () => [],
            'rowActions' => fn () => [],
            'bulkActions' => fn () => [],
            'defaultSort' => fn () => [],
            'defaultFilter' => fn () => [],
            'defaultSelect' => fn () => [],
            'runtimeFields' => fn () => [],
            'indexSelect' => fn () => [],
            'indexFilter' => fn () => [],
            'indexOrder' => fn () => [],
            'indexRuntime' => fn () => [],
            'beforeIndexQueryParams' => fn ($p) => $p,
            'afterIndexRows' => fn ($r) => $r,
            'mapIndexRow' => fn ($r) => $r,
            'modifyIndexParams' => fn ($p) => $p,
        ];

        return new IndexPageDefinition(array_merge($defaults, $overrides));
    }
}

class MockDataManager
{
    public static int $runtimeCountCalls = 0;

    public static function getCount($filter = [], $cache = []): int
    {
        return 100;
    }

    public static function query(): MockCountQuery
    {
        return new MockCountQuery();
    }

    public static function getList($params): object
    {
        return new class () {
            public function fetch()
            {
                return false;
            }
            public function getSelectedRowsCount()
            {
                return 0;
            }
        };
    }
}

final class MockCountQuery
{
    /** @var array<int|string, mixed> */
    public array $runtime = [];

    /** @var array<string, mixed> */
    public array $filter = [];

    public function registerRuntimeField(mixed $name, mixed $fieldInfo = null): self
    {
        ++MockDataManager::$runtimeCountCalls;
        $this->runtime[$name] = $fieldInfo;

        return $this;
    }

    /** @param array<string, mixed> $filter */
    public function setFilter(array $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function addSelect(mixed $field): self
    {
        return $this;
    }

    public function exec(): object
    {
        return new class () {
            public function fetch(): array
            {
                return ['CNT' => 7];
            }
        };
    }
}
