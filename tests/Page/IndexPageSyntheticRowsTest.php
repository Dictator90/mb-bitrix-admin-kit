<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class IndexPageSyntheticRowsTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
        $_POST = [];
    }

    public function testSelectedGroupIdsIgnoredAndItemIdsNormalized(): void
    {
        $_POST['id'] = ['group:10', 'item:55', '77'];
        $page = new class (new ProductResource()) extends IndexPage {
            public function selected(): array
            {
                return $this->resolveSelectedIds();
            }
        };

        self::assertSame(['55', '77'], $page->selected());
    }

    public function testInlineEditGroupRowIgnoredAndItemIdNormalized(): void
    {
        $page = new class (new ProductResource()) extends IndexPage {
            public function save(mixed $id, array $payload): array
            {
                return $this->saveInlineRow($id, $payload);
            }
        };

        self::assertSame([], $page->save('group:10', ['NAME' => 'Nope']));
        self::assertSame([], $page->save('item:1', ['NAME' => 'Updated']));
        self::assertSame('1', (string)ProductTable::$lastUpdated['id']);
    }
}
