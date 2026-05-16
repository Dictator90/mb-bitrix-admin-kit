<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Manager\AdminKitMenuBuilder;
use MB\Bitrix\AdminKit\Manager\AdminKitRegistry;
use MB\Bitrix\AdminKit\Manager\AdminKitRouter;
use MB\Bitrix\AdminKit\Manager\ResourcePage;
use MB\Bitrix\AdminKit\Page\Standalone\CustomPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use PHPUnit\Framework\TestCase;

final class AdminKitV080Test extends TestCase
{
    public function testRegistryStoresSortedResourcesAndPages(): void
    {
        $registry = (new AdminKitRegistry())
            ->registerResource(LateResource::class)
            ->registerResource(EarlyResource::class)
            ->registerPage(StandalonePage::class);

        self::assertSame(['early', 'late'], array_keys($registry->resources()));
        self::assertSame(['standalone'], array_keys($registry->pages()));
    }

    public function testRouterResolvesStandalonePageBeforeResource(): void
    {
        $registry = (new AdminKitRegistry())->registerResource(EarlyResource::class)->registerPage(StandalonePage::class);
        $router = new AdminKitRouter($registry, new class () extends HttpRequest {
            public function get(string $key): mixed
            {
                return $key === 'page' ? 'standalone' : null;
            } public function getPost(string $key): mixed
            {
                return null;
            }
        });

        self::assertInstanceOf(StandalonePage::class, $router->currentPage());
    }

    public function testRouterResolvesResourcePage(): void
    {
        $registry = (new AdminKitRegistry())->registerResource(EarlyResource::class);
        $router = new AdminKitRouter($registry, new class () extends HttpRequest {
            public function get(string $key): mixed
            {
                return $key === 'page' ? 'early' : null;
            } public function getPost(string $key): mixed
            {
                return null;
            }
        });

        self::assertInstanceOf(ResourcePage::class, $router->currentPage());
    }

    public function testMenuBuilderGroupsSortsAndSkipsDeniedItems(): void
    {
        $registry = (new AdminKitRegistry())
            ->registerResource(EarlyResource::class)
            ->registerResource(DeniedResource::class)
            ->registerPage(StandalonePage::class);

        $menu = (new AdminKitMenuBuilder($registry, '/bitrix/admin/test.php'))->build();
        $flatTexts = array_column($menu, 'text');

        self::assertContains('reports', $flatTexts);
        self::assertNotContains('Denied', $flatTexts);
        $group = array_values(array_filter($menu, static fn (array $item): bool => $item['text'] === 'reports'))[0];
        self::assertSame('Early', $group['items'][0]['text']);
        self::assertStringContainsString('page=early', $group['items'][0]['url']);
    }
}

abstract class BaseTestResource extends Resource
{
    protected string $title = 'Base';
    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}

final class EarlyResource extends BaseTestResource
{
    protected string $title = 'Early';
    public static function getId(): string
    {
        return 'early';
    }
    public static function getSort(): int
    {
        return 10;
    }
    public static function getParentMenuId(): ?string
    {
        return 'reports';
    }
}

final class LateResource extends BaseTestResource
{
    protected string $title = 'Late';
    public static function getId(): string
    {
        return 'late';
    }
    public static function getSort(): int
    {
        return 20;
    }
}

final class DeniedResource extends BaseTestResource
{
    protected string $title = 'Denied';
    public static function getId(): string
    {
        return 'denied';
    }
    public function canView(?\MB\Bitrix\AdminKit\Security\PermissionContext $context = null): bool
    {
        return false;
    }
}

final class StandalonePage extends CustomPage
{
    public static function getId(): string
    {
        return 'standalone';
    }
    public static function getTitle(): string
    {
        return 'Standalone';
    }
    public static function getSort(): int
    {
        return 15;
    }
    protected function content(): string
    {
        return 'content';
    }
}
