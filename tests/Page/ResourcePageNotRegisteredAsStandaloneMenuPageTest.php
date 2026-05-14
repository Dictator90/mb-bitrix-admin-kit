<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Manager\AdminKitMenuBuilder;
use MB\Bitrix\AdminKit\Manager\AdminKitRegistry;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ResourcePageNotRegisteredAsStandaloneMenuPageTest extends TestCase
{
    public function testResourcePageNotRegisteredAsStandaloneMenuPage(): void
    {
        $registry = (new AdminKitRegistry())
            ->registerResource(ProductResourceWithCustomIndexPage::class)
            ->registerPage(ProductIndexPage::class);

        self::assertSame([], $registry->pages());

        $menu = (new AdminKitMenuBuilder($registry, '/admin/products.php'))->build();
        self::assertCount(1, $menu);
        self::assertSame('Products', $menu[0]['text']);
    }

    public function testProductIndexPageResolvedOnlyThroughResourcePages(): void
    {
        $resource = new ProductResourceWithCustomIndexPage();
        $page = (new ResourcePageResolver())->resolve($resource, 'index');

        self::assertInstanceOf(ProductIndexPage::class, $page);
    }

    public function testDiscoveredResourcePageClassIsNotStandalonePage(): void
    {
        $dir = sys_get_temp_dir() . '/adminkit_resource_page_' . str_replace('.', '_', uniqid('', true));
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/DiscoveredProductIndexPage.php', <<<'PHP_CODE'
<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\DiscoveredResourcePages;

use MB\Bitrix\AdminKit\Page\IndexPage;

final class DiscoveredProductIndexPage extends IndexPage
{
}
PHP_CODE);

        $registry = (new AdminKitRegistry())->discoverPath($dir);

        self::assertSame([], $registry->pages());
    }
}

final class ProductResourceWithCustomIndexPage extends ProductResource
{
    public static function getId(): string
    {
        return 'products';
    }

    public function getTitle(): string
    {
        return 'Products';
    }

    public function pages(): iterable
    {
        return [ProductIndexPage::class];
    }
}

final class ProductIndexPage extends IndexPage
{
}
