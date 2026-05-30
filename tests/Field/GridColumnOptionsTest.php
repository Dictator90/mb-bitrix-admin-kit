<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class GridColumnOptionsTest extends TestCase
{
    public function testColumnOptionsAppearInConfigWhenSet(): void
    {
        $config = Text::make('Name', 'NAME')
            ->width(120)
            ->align('center')
            ->color('#ff0000')
            ->sticked()
            ->getGridColumnConfig();

        self::assertSame(120, $config['width']);
        self::assertSame('center', $config['align']);
        self::assertSame('#ff0000', $config['color']);
        self::assertTrue($config['sticked']);
    }

    public function testColumnOptionsAbsentByDefault(): void
    {
        $config = Text::make('Name', 'NAME')->getGridColumnConfig();

        self::assertArrayNotHasKey('width', $config);
        self::assertArrayNotHasKey('align', $config);
        self::assertArrayNotHasKey('color', $config);
        self::assertArrayNotHasKey('sticked', $config);
    }

    public function testInvalidAlignThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Text::make('Name', 'NAME')->align('middle');
    }
}
