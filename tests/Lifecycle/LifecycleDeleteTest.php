<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Lifecycle;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class LifecycleDeleteTest extends TestCase
{
    public function testDeleteHooksAreCalled(): void
    {
        $resource = new class () extends ProductResource {
            public array $calls = [];
            public function beforeDelete(array $item, DbOperationContext $context): void
            {
                $this->calls[] = 'before:' . $item['NAME'];
            }
            public function afterDelete(array $item, DbOperationContext $context): void
            {
                $this->calls[] = 'after:' . $item['NAME'];
            }
        };

        $resource->deleteItem(1);

        self::assertSame(['before:One', 'after:One'], $resource->calls);
    }
}
