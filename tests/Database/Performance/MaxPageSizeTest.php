<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Database\Performance;

use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class MaxPageSizeTest extends TestCase
{
    public function testGridLimitIsCappedByResourceMaxPageSize(): void
    {
        $resource = new class extends ProductResource { public function maxPageSize(): int { return 50; } };
        $context = GridContext::make($resource, null, ['limit' => 500, 'pageSize' => 500]);

        $params = (new QueryGuard())->guardGridParams(['limit' => 500], $context);

        self::assertSame(50, $params['limit']);
    }
}
