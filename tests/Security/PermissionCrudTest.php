<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Security;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class PermissionCrudTest extends TestCase
{
    public function testCrudResourceAcceptsPermissionContext(): void
    {
        $resource = new ProductResource();
        $context = new PermissionContext(resource: $resource, operation: 'create');

        self::assertTrue($resource->canCreate($context));
        self::assertTrue($resource->canView(new PermissionContext(resource: $resource, operation: 'view')));
        self::assertTrue($resource->canUpdate(new PermissionContext(resource: $resource, operation: 'update')));
        self::assertTrue($resource->canDelete(new PermissionContext(resource: $resource, operation: 'delete')));
    }
}
