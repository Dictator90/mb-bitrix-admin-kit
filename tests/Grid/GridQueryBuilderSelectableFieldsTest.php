<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderSelectableFieldsTest extends TestCase
{
    public function testSelectRespectsSelectableAndSelectColumns(): void
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
                Text::make('A', 'COL_A'),
                Text::make('B', 'COL_B')->selectable(false),
                Text::make('C', 'COL_C')->selectColumns(['COL_C1', 'COL_C2']),
            ],
        ]);

        $builder = new GridQueryBuilder();
        $context = new GridContext($resource, 'grid', 'filter');
        $params = $builder->build($resource, $context, $definition);

        self::assertContains('COL_A', $params['select']);
        self::assertNotContains('COL_B', $params['select']);
        self::assertContains('COL_C1', $params['select']);
        self::assertContains('COL_C2', $params['select']);
        self::assertNotContains('COL_C', $params['select']);
        self::assertContains('ID', $params['select']); // Primary key
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
