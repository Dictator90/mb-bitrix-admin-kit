<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\RelationTileGridPreviewRenderer;
use PHPUnit\Framework\TestCase;

final class RelationTileGridPreviewRendererTest extends TestCase
{
    public function testRenderOutputsTileGridContainerAndInitScript(): void
    {
        $html = (new RelationTileGridPreviewRenderer())->render(
            [
                ['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5],
            ],
            'USER_GROUP',
        );

        self::assertStringContainsString('adminkit-relation-tilegrid', $html);
        self::assertStringContainsString('ui.tilegrid', $html);
        self::assertStringContainsString('RelationTileGrid', $html);
        self::assertStringContainsString('USER_ID', $html);
    }

    public function testResolveColumnIdsPutsIdFirst(): void
    {
        $columns = RelationTileGridPreviewRenderer::resolveColumnIds([
            ['GROUP_ID' => 5, 'ID' => 1, 'USER_ID' => 11],
        ]);

        self::assertSame(['ID', 'GROUP_ID', 'USER_ID'], $columns);
    }

    public function testRenderUsesConfiguredColumnLabels(): void
    {
        $html = (new RelationTileGridPreviewRenderer())->render(
            [
                ['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5],
            ],
            'USER_GROUP',
            ['USER_ID' => 'Пользователь', 'ID' => 'Идентификатор'],
        );

        self::assertStringContainsString('Пользователь', $html);
        self::assertStringContainsString('Идентификатор', $html);
        self::assertStringNotContainsString('"GROUP_ID"', $html);
    }
}
