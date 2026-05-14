<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Import\ImportAction;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ImportPermissionTest extends TestCase
{
    public function testItRequiresCreatePermissionForCreateImport(): void
    {
        $resource = new class extends ProductResource {
            public function canCreate(?PermissionContext $context = null): bool { return false; }
        };

        $result = ImportAction::make()->import(new ImportContext($resource, mappedRows: [['NAME' => 'One']], mode: 'create'));

        self::assertFalse($result->isSuccess());
        self::assertArrayHasKey('permission', $result->errors);
    }
}
