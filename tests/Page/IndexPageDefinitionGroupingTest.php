<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Page\ResourceBackedIndexPageDefinition;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Grid\GroupedRowsBuilderGroupResource;
use PHPUnit\Framework\TestCase;

final class IndexPageDefinitionGroupingTest extends TestCase
{
    public function testDefinitionWithoutGroupingCallbackReturnsNull(): void
    {
        self::assertNull((new IndexPageDefinition($this->callbacks()))->grouping());
    }

    public function testDefinitionWithGroupingCallbackReturnsGrouping(): void
    {
        $grouping = IndexGrouping::make()->resource(GroupedRowsBuilderGroupResource::class)->foreignKey('GROUP_ID');
        $callbacks = $this->callbacks();
        $callbacks['grouping'] = static fn (): IndexGrouping => $grouping;

        self::assertSame($grouping, (new IndexPageDefinition($callbacks))->grouping());
    }

    public function testResourceBackedDefinitionReturnsResourceGrouping(): void
    {
        $grouping = IndexGrouping::make()->resource(GroupedRowsBuilderGroupResource::class)->foreignKey('GROUP_ID');
        $resource = new class ($grouping) extends ProductResource {
            public function __construct(private IndexGrouping $grouping)
            {
            }

            public function indexGrouping(): ?IndexGrouping
            {
                return $this->grouping;
            }
        };

        self::assertSame($grouping, (new ResourceBackedIndexPageDefinition($resource))->grouping());
    }

    private function callbacks(): array
    {
        return [
            'fields' => static fn (): array => [],
            'filters' => static fn (): array => [],
            'rowActions' => static fn (): array => [],
            'bulkActions' => static fn (): array => [],
            'defaultSort' => static fn (): array => [],
            'defaultFilter' => static fn (): array => [],
            'defaultSelect' => static fn (): array => [],
            'runtimeFields' => static fn (): array => [],
            'indexSelect' => static fn (): array => [],
            'indexFilter' => static fn (): array => [],
            'indexOrder' => static fn (): array => [],
            'indexRuntime' => static fn (): array => [],
            'beforeIndexQueryParams' => static fn (array $params): array => $params,
            'afterIndexRows' => static fn (array $rows): array => $rows,
            'mapIndexRow' => static fn (array $row): array => $row,
            'modifyIndexParams' => static fn (array $params): array => $params,
        ];
    }
}
