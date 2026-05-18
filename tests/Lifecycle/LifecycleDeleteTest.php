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
            public function beforeDelete(mixed $id, DbOperationContext $context): void
            {
                $this->calls[] = 'before:' . ($context->oldData['NAME'] ?? '');
            }
            public function afterDelete(mixed $id, DbOperationContext $context): void
            {
                $this->calls[] = 'after:' . ($context->oldData['NAME'] ?? '');
            }
        };

        $resource->deleteItem(1);

        self::assertSame(['before:One', 'after:One'], $resource->calls);
    }
}
