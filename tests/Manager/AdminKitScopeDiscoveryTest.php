<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use MB\Bitrix\AdminKit\AdminKit;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitRegistry;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;
use MB\Bitrix\AdminKit\Manager\DiscoveryConfig;
use MB\Bitrix\AdminKit\Page\Standalone\CustomPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use PHPUnit\Framework\TestCase;

final class AdminKitScopeDiscoveryTest extends TestCase
{
    public function testAdminKitScopeFromStringModuleTest(): void
    {
        $scope = AdminKitScope::fromModule('vendor.module');

        self::assertSame('vendor.module', $scope->scopeId());
        self::assertSame([], $scope->discoveryPaths());
    }

    public function testAdminKitScopeFromObjectModuleTest(): void
    {
        $module = new class () {
            public string $moduleId = 'vendor.object';
            public string $path = '/module/path';
        };

        $scope = AdminKitScope::fromModule($module);

        self::assertSame('vendor.object', $scope->id());
        self::assertSame(['/module/path/lib'], $scope->discoveryPaths());
    }

    public function testAdminKitScopeFromObjectWithGetLibPathTest(): void
    {
        $module = new class () {
            public function getModuleId(): string
            {
                return 'vendor.lib';
            }
            public function getPath(): string
            {
                return '/module';
            }
        };

        $scope = AdminKitScope::fromModule($module);

        self::assertSame('vendor.lib', $scope->scopeId());
        self::assertSame(['/module/lib'], $scope->discoveryPaths());
    }

    public function testAdminKitScopeFromScopeTest(): void
    {
        $scope = AdminKitScope::fromScope('site.admin');

        self::assertSame('site.admin', $scope->scopeId());
        self::assertSame([], $scope->discoveryPaths());
    }

    public function testAdminKitScopeFromDirectoryTest(): void
    {
        $scope = AdminKitScope::fromDirectory('/local/php_interface/lib/Admin', 'site.admin');

        self::assertSame('site.admin', $scope->scopeId());
        self::assertSame(['/local/php_interface/lib/Admin'], $scope->discoveryPaths());
    }

    public function testAdminKitScopeFromDirectoriesTest(): void
    {
        $scope = AdminKitScope::fromDirectories(['/admin', '/tools'], 'site.admin');

        self::assertSame('site.admin', $scope->scopeId());
        self::assertSame(['/admin', '/tools'], $scope->discoveryPaths());
    }

    public function testAdminKitForModuleStringTest(): void
    {
        $manager = AdminKit::forModule('vendor.module');

        self::assertInstanceOf(AdminKitManager::class, $manager);
        self::assertSame('vendor.module', $manager->scopeId());
    }

    public function testAdminKitForModuleObjectTest(): void
    {
        $manager = AdminKit::forModule(new class () {
            public string $id = 'object.module';
        });

        self::assertSame('object.module', $manager->scopeId());
    }

    public function testAdminKitForScopeTest(): void
    {
        self::assertSame('site.admin', AdminKit::forScope('site.admin')->scopeId());
    }

    public function testAdminKitFromDirectoriesTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $manager = AdminKit::fromDirectories([$first, $second], 'site.admin');

        self::assertSame('site.admin', $manager->scopeId());
        self::assertSame([$first, $second], $manager->scope()->discoveryPaths());
    }

    public function testAdminKitDoesNotRequireModuleEntityContractTest(): void
    {
        $manager = AdminKit::manager('vendor.module');

        self::assertSame('vendor.module', $manager->scopeId());
    }

    public function testDiscoveryConfigTest(): void
    {
        $dir = $this->makeDirectory();
        $config = (new DiscoveryConfig())->addPath('')->addPath('/missing/path')->addPath($dir)->addPath($dir . '/');

        self::assertFalse($config->isEmpty());
        self::assertSame([str_replace('\\', '/', $dir)], array_map(
            static fn (string $path): string => str_replace('\\', '/', $path),
            $config->paths(),
        ));
    }

    public function testAdminKitManagerDiscoverInTest(): void
    {
        $dir = $this->makeDirectory();
        $this->writeResource($dir, 'DiscoveredInResource', 'discovered-in');

        $manager = AdminKit::forScope('site.admin')->discoverIn($dir);

        self::assertArrayHasKey('discovered-in', $manager->getResources());
    }

    public function testAdminKitManagerDiscoverPathsTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $this->writeResource($first, 'FirstPathsResource', 'first-paths');
        $this->writePage($second, 'SecondPathsPage', 'second-paths');

        $manager = AdminKit::forScope('site.admin')->discoverPaths([$first, $second]);

        self::assertArrayHasKey('first-paths', $manager->getResources());
        self::assertArrayHasKey('second-paths', $manager->getPages());
    }

    public function testAdminKitManagerEmptyDiscoveryPathsTest(): void
    {
        $manager = AdminKit::forScope('site.admin');

        self::assertSame([], $manager->getResources());
        self::assertSame([], $manager->getPages());
    }

    public function testAdminKitManagerManualRegisterWithoutDiscoveryTest(): void
    {
        $manager = AdminKit::forScope('site.admin')->register(ManualScopeResource::class)->registerPage(ManualScopePage::class);

        self::assertSame(ManualScopeResource::class, $manager->getResources()['manual-scope']);
        self::assertSame(ManualScopePage::class, $manager->getPages()['manual-scope-page']);
    }

    public function testAdminKitRegistryDiscoverPathTest(): void
    {
        $dir = $this->makeDirectory();
        $this->writeResource($dir, 'RegistryPathResource', 'registry-path');

        $registry = (new AdminKitRegistry())->discoverPath($dir);

        self::assertArrayHasKey('registry-path', $registry->resources());
    }

    public function testAdminKitRegistryDiscoverPathsTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $this->writeResource($first, 'RegistryFirstResource', 'registry-first');
        $this->writeResource($second, 'RegistrySecondResource', 'registry-second');

        $registry = (new AdminKitRegistry())->discoverPaths([$first, $second]);

        self::assertArrayHasKey('registry-first', $registry->resources());
        self::assertArrayHasKey('registry-second', $registry->resources());
    }

    public function testAdminKitRegistryDuplicatePathsTest(): void
    {
        $dir = $this->makeDirectory();
        $this->writeResource($dir, 'DuplicatePathResource', 'duplicate-path');

        $registry = (new AdminKitRegistry())->discoverPaths([$dir, $dir . '/']);

        self::assertCount(1, $registry->resources());
    }

    public function testAdminKitRegistryIgnoreMissingPathTest(): void
    {
        $registry = (new AdminKitRegistry())->discoverPath('/missing/path');

        self::assertSame([], $registry->resources());
        self::assertSame([], $registry->pages());
    }

    public function testAdminKitRegistryManualAndDiscoveryTogetherTest(): void
    {
        $dir = $this->makeDirectory();
        $this->writeResource($dir, 'TogetherResource', 'together');

        $registry = (new AdminKitRegistry())->registerResource(ManualScopeResource::class)->discoverPath($dir);

        self::assertArrayHasKey('manual-scope', $registry->resources());
        self::assertArrayHasKey('together', $registry->resources());
    }

    public function testAdminKitRegistryDoesNotRegisterAbstractClassTest(): void
    {
        $dir = $this->makeDirectory();
        $this->writeResource($dir, 'AbstractDiscoveryResource', 'abstract-discovery', abstract: true);

        $registry = (new AdminKitRegistry())->discoverPath($dir);

        self::assertSame([], $registry->resources());
    }

    private function makeDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/adminkit_' . str_replace('.', '_', uniqid('', true));
        mkdir($dir, 0777, true);

        return $dir;
    }

    private function writeResource(string $dir, string $class, string $id, bool $abstract = false): void
    {
        $abstractPrefix = $abstract ? 'abstract ' : '';
        file_put_contents($dir . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\DiscoveryFixtures;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\Resource;

{$abstractPrefix}class {$class} extends Resource
{
    public static function getId(): string
    {
        return '{$id}';
    }

    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}
PHP_CODE);
    }

    private function writePage(string $dir, string $class, string $id): void
    {
        file_put_contents($dir . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\DiscoveryFixtures;

use MB\Bitrix\AdminKit\Page\Standalone\CustomPage;

final class {$class} extends CustomPage
{
    public static function getId(): string
    {
        return '{$id}';
    }

    public static function getTitle(): string
    {
        return '{$id}';
    }

    protected function content(): string
    {
        return '{$id}';
    }
}
PHP_CODE);
    }
}

final class ManualScopeResource extends Resource
{
    public static function getId(): string
    {
        return 'manual-scope';
    }

    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}

final class ManualScopePage extends CustomPage
{
    public static function getId(): string
    {
        return 'manual-scope-page';
    }

    public static function getTitle(): string
    {
        return 'Manual page';
    }

    protected function content(): string
    {
        return 'Manual page';
    }
}
