<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Manager\AdminKitRegistry;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class DashboardPageIsStandalonePageTest extends TestCase
{
    public function testDashboardPageIsStandalonePage(): void
    {
        self::assertTrue(StandaloneDashboardPageForPagesTest::isStandalone());
        self::assertInstanceOf(DashboardPage::class, new StandaloneDashboardPageForPagesTest());
    }

    public function testDashboardPageDoesNotRequireCrudResource(): void
    {
        $page = new StandaloneDashboardPageForPagesTest();

        self::assertSame('dashboard', $page->id());
        self::assertSame('Dashboard', $page->title());
    }

    public function testDashboardPageNotInDefaultResourcePages(): void
    {
        self::assertSame(
            [IndexPage::class, FormPage::class, DetailPage::class],
            iterator_to_array((new ProductResource())->pages()),
        );
    }

    public function testDashboardPageCanBeRegisteredAsStandalonePage(): void
    {
        $registry = (new AdminKitRegistry())->registerPage(StandaloneDashboardPageForPagesTest::class);

        self::assertSame(StandaloneDashboardPageForPagesTest::class, $registry->pages()['dashboard']);
        self::assertSame([], $registry->resources());
    }

    public function testDashboardPageCanBeDiscoveredAsStandalonePage(): void
    {
        $dir = sys_get_temp_dir() . '/adminkit_dashboard_' . str_replace('.', '_', uniqid('', true));
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/DiscoveredDashboardPage.php', <<<'PHP_CODE'
<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page\DiscoveredDashboard;

use MB\Bitrix\AdminKit\Pages\DashboardPage;

final class DiscoveredDashboardPage extends DashboardPage
{
    public static function getId(): string
    {
        return 'discovered-dashboard';
    }

    public static function getTitle(): string
    {
        return 'Discovered Dashboard';
    }
}
PHP_CODE);

        $registry = (new AdminKitRegistry())->discoverPath($dir);

        self::assertArrayHasKey('discovered-dashboard', $registry->pages());
    }
}

final class StandaloneDashboardPageForPagesTest extends DashboardPage
{
    public static function getId(): string
    {
        return 'dashboard';
    }

    public static function getTitle(): string
    {
        return 'Dashboard';
    }
}
