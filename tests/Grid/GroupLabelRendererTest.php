<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grouping\GroupLabelRenderer;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GroupLabelRendererTest extends TestCase
{
    public function testItUsesGroupDataNameWhenLabelIsNotConfigured(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(ProductResource::class)
            ->foreignKey('GROUP_ID');

        $html = (new GroupLabelRenderer())->render([
            '__GROUP_ID' => 5,
            '__GROUP_DATA' => ['ID' => 5, 'NAME' => 'Раздел A'],
            '__GROUP_RESOURCE' => ProductResource::class,
        ], $grouping, '/admin/');

        self::assertStringContainsString('Раздел A', $html);
        self::assertStringContainsString('id=5', $html);
    }

    public function testItRendersUngroupedLabel(): void
    {
        $grouping = IndexGrouping::make()
            ->resource(ProductResource::class)
            ->foreignKey('GROUP_ID')
            ->ungroupedLabel('Без раздела');

        $html = (new GroupLabelRenderer())->render([
            '__GROUP_ID' => '__ungrouped',
            '__GROUP_DATA' => [],
        ], $grouping, '/admin/');

        self::assertSame('Без раздела', $html);
    }
}
