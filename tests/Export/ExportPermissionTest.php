<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Export;

use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ExportPermissionTest extends TestCase
{
    public function testItRequiresResourceViewPermission(): void
    {
        $resource = new class () extends ProductResource {
            public function canView(?PermissionContext $context = null): bool
            {
                return false;
            }
        };

        $result = ExportAction::make()->execute(new ExportContext($resource, selectedIds: [1]));

        self::assertFalse($result->isSuccess());
        self::assertSame(['Export permission denied.'], $result->errors);
    }

    public function testItHonorsCanRunCondition(): void
    {
        $result = ExportAction::make()->canRun(false)->execute(new ExportContext(new ProductResource(), selectedIds: [1]));

        self::assertFalse($result->isSuccess());
        self::assertSame(['Export action is not allowed.'], $result->errors);
    }
}
