<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\AdminString;
use PHPUnit\Framework\TestCase;

final class AdminStringTest extends TestCase
{
    public function testItBuildsStableIds(): void
    {
        self::assertSame('product', AdminString::resourceId('App\\Admin\\ProductResource'));
        self::assertStringStartsWith('ADMINKIT_GRID_', AdminString::gridId('product'));
    }

    public function testSlugTransliteratesCyrillicViaBitrix(): void
    {
        if (!class_exists(\CUtil::class) || !method_exists(\CUtil::class, 'translit')) {
            self::markTestSkipped('CUtil::translit is not available.');
        }

        self::assertSame('primer-tovara', AdminString::slug('Пример товара', '-'));
    }
}
