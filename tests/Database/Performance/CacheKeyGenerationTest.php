<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Support\AdminString;
use PHPUnit\Framework\TestCase;

final class CacheKeyGenerationTest extends TestCase
{
    public function testCacheKeyUsesAdminString(): void
    {
        $key = AdminString::cacheKey('adminkit_count', ['filter' => ['NAME' => 'One']]);

        self::assertStringStartsWith('adminkit_count_', $key);
        self::assertMatchesRegularExpression('/^[a-z0-9_]+$/', $key);
    }
}
