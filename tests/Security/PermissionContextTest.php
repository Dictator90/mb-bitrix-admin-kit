<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Security;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use PHPUnit\Framework\TestCase;

final class PermissionContextTest extends TestCase
{
    public function testStoresPermissionData(): void
    {
        $context = new PermissionContext(userId: 1, moduleId: 'module', operation: 'update', item: ['ID' => 5]);

        self::assertSame(1, $context->userId);
        self::assertSame('module', $context->moduleId);
        self::assertSame('update', $context->operation);
        self::assertSame(['ID' => 5], $context->item);
    }
}
