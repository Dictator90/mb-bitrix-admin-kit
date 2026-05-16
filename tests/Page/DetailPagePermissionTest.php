<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class DetailPagePermissionTest extends TestCase
{
    public function testNotFoundShowsLocalizedMessage(): void
    {
        $resource = new class () extends ProductResource {
            public function findItem(mixed $id): ?array
            {
                return null;
            }
        };

        $page = new DetailPage($resource, 99);

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('Элемент не найден.', $html);
        self::assertStringNotContainsString('ui-form-row', $html);
    }

    public function testCannotViewDoesNotRenderRecordData(): void
    {
        $resource = new class () extends ProductResource {
            public function findItem(mixed $id): ?array
            {
                return ['ID' => $id, 'NAME' => 'Secret product'];
            }

            public function canView(?PermissionContext $context = null): bool
            {
                return false;
            }
        };

        $page = new DetailPage($resource, 1);

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('Недостаточно прав для просмотра записи.', $html);
        self::assertStringNotContainsString('Secret product', $html);
        self::assertStringNotContainsString('ui-form-row', $html);
    }
}
