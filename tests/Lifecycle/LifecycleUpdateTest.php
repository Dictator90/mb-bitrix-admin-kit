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
        $resource = new class extends ProductResource {
            public array $calls = [];
            public function beforeUpdate(array $oldItem, FormData $data, DbOperationContext $context): void { $this->calls[] = 'before:' . $oldItem['NAME']; }
            public function afterUpdate(array $item, FormData $data, DbOperationContext $context): void { $this->calls[] = 'after:' . $item['NAME']; }
        };

        $resource->updateItem(1, ['NAME' => 'New']);

        self::assertSame(['before:One', 'after:New'], $resource->calls);
    }
}
