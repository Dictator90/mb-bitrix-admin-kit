<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Lifecycle;

use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class LifecycleUpdateTest extends TestCase
{
    public function testUpdateHooksAreCalled(): void
    {
        $resource = new class () extends ProductResource {
            public array $calls = [];
            public function beforeUpdate(mixed $id, FormData $data, DbOperationContext $context): void
            {
                $this->calls[] = 'before:' . ($context->oldData['NAME'] ?? '');
            }
            public function afterUpdate(mixed $id, FormData $data, DbOperationContext $context): void
            {
                $this->calls[] = 'after:' . ($data->validated()['NAME'] ?? '');
            }
        };

        $resource->updateItem(1, ['NAME' => 'New']);

        self::assertSame(['before:One', 'after:New'], $resource->calls);
    }
}
