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
}
