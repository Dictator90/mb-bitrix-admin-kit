<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderSortValidationTest extends TestCase
{
    public function testOrderIsSanitizedAgainstAllowedColumns(): void
    {
        $resource = new class () extends DataManagerResource {
            public static function getId(): string
            {
                return 'test';
            }
            public function getPrimaryKey(): string
            {
                return 'ID';
            }
            public function dataManagerClass(): string
            {
                return 'SomeTable';
            }
        };

        $definition = $this->makeDefinition([
            'fields' => fn () => [
                Text::make('Name', 'NAME')->sortable(true),
                Text::make('Code', 'CODE')->sortable(false),
            ],
            'defaultSort' => fn () => ['ID' => 'DESC'],
        ]);

        $builder = new GridQueryBuilder();

        // Allowed sort
        $context = new GridContext($resource, 'grid', 'filter', ['NAME' => 'DESC']);
        $params = $builder->build($resource, $context, $definition);
        self::assertEquals(['NAME' => 'DESC'], $params['order']);

        // Unknown column - should fallback to defaultSort
        $context = new GridContext($resource, 'grid', 'filter', ['UNKNOWN' => 'ASC']);
        $params = $builder->build($resource, $context, $definition);
        self::assertEquals(['ID' => 'DESC'], $params['order']);

        // Non-sortable column - should fallback to defaultSort
        $context = new GridContext($resource, 'grid', 'filter', ['CODE' => 'ASC']);
        $params = $builder->build($resource, $context, $definition);
        self::assertEquals(['ID' => 'DESC'], $params['order']);
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
