<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridContextTest extends TestCase
{
    public function testItStoresGridState(): void
    {
        $ctx = GridContext::make(new ProductResource(), null, ['limit' => 10, 'offset' => 20]);
        self::assertSame(10, $ctx->limit);
        self::assertSame(20, $ctx->offset);
    }
}
