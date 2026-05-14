<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Lifecycle;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class LifecycleCreateTest extends TestCase
{
    public function testCreateHooksAreCalled(): void
    {
        $resource = new class extends ProductResource {
            public array $calls = [];
            public function beforeCreate(FormData $data, DbOperationContext $context): void { $this->calls[] = 'before:' . $context->operation; }
            public function afterCreate(mixed $id, FormData $data, DbOperationContext $context): void { $this->calls[] = 'after:' . $id; }
        };

        $resource->createItem(['NAME' => 'Two']);

        self::assertSame(['before:create', 'after:2'], $resource->calls);
    }
}
