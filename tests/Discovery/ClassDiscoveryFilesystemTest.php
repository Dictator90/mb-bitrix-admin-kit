<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Discovery;

use MB\Bitrix\AdminKit\Discovery\ClassDiscovery;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Manager\AdminKitRegistry;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Filesystem\Filesystem;
use MB\Filesystem\Finder\ClassFinder;
use PHPUnit\Framework\TestCase;

final class ClassDiscoveryFilesystemTest extends TestCase
{
    /** @var list<string> */
    private array $directories = [];

    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) {
            $this->deleteDirectory($directory);
        }

        $this->directories = [];
    }

    public function testClassDiscoveryUsesFilesystemClassFinderTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $class = 'FinderUsageResource';
        $this->writeResource($directory, $namespace, $class, 'finder-usage');

        $finder = new SpyClassFinder(new Filesystem());
        $discovery = new ClassDiscovery($finder);

        self::assertContains($namespace . '\\' . $class, $discovery->resourcesIn($directory));
        self::assertSame([[$directory, Resource::class, true]], $finder->extendsCalls);
    }

    public function testClassDiscoveryFindsResourceDescendantsTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $class = 'DirectResource';
        $this->writeResource($directory, $namespace, $class, 'direct-resource');

        self::assertContains($namespace . '\\' . $class, (new ClassDiscovery())->resourcesIn($directory));
    }

    public function testClassDiscoveryFindsDeepResourceDescendantsTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $this->writeBaseResource($directory, $namespace, 'BaseProductResource');
        $this->writeResource($directory, $namespace, 'ProductResource', 'product', 'BaseProductResource');

        self::assertContains($namespace . '\\ProductResource', (new ClassDiscovery())->resourcesIn($directory));
    }

    public function testClassDiscoveryFindsExternalBaseResourceDescendantsWithReflectionTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $class = 'ExternalBaseProductResource';
        $this->writeResource($directory, $namespace, $class, 'external-base-product', '\\' . BaseDiscoveryResource::class);

        self::assertContains($namespace . '\\' . $class, (new ClassDiscovery())->resourcesIn($directory));
    }

    public function testClassDiscoveryFindsStandalonePagesTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $this->writePageBase($directory, $namespace, 'BaseDashboardPage');
        $this->writePage($directory, $namespace, 'SalesDashboardPage', 'sales-dashboard', 'BaseDashboardPage');

        self::assertContains($namespace . '\\SalesDashboardPage', (new ClassDiscovery())->standalonePagesIn($directory));
    }

    public function testClassDiscoveryDoesNotReturnResourcePagesAsStandalonePagesTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        file_put_contents($directory . '/ProductIndexPage.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace {$namespace};

class ProductIndexPage extends \\MB\\Bitrix\\AdminKit\\Page\\IndexPage
{
}
PHP_CODE);

        self::assertSame([], (new ClassDiscovery())->standalonePagesIn($directory));
    }

    public function testClassDiscoveryIgnoresAbstractClassesTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $this->writeBaseResource($directory, $namespace, 'AbstractOnlyResource');

        self::assertSame([], (new ClassDiscovery())->resourcesIn($directory));
    }

    public function testClassDiscoveryIgnoresMissingPathTest(): void
    {
        $discovery = new ClassDiscovery();

        self::assertSame([], $discovery->resourcesIn('/missing/path'));
        self::assertSame([], $discovery->standalonePagesIn('/missing/path'));
    }

    public function testAdminKitRegistryUsesClassDiscoveryTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $this->writeResource($directory, $namespace, 'RegistryDiscoveryResource', 'registry-discovery');
        $finder = new SpyClassFinder(new Filesystem());

        (new AdminKitRegistry(new ClassDiscovery($finder)))->discoverPath($directory);

        self::assertSame([[$directory, Resource::class, true], [$directory, \MB\Bitrix\AdminKit\Pages\AbstractPage::class, true]], $finder->extendsCalls);
    }

    public function testAdminKitRegistryDiscoverPathWithFilesystemTest(): void
    {
        $directory = $this->makeDirectory();
        $namespace = $this->fixtureNamespace();
        $this->writeResource($directory, $namespace, 'RegistryPathFilesystemResource', 'registry-path-filesystem');

        $registry = (new AdminKitRegistry())->discoverPath($directory);

        self::assertSame($namespace . '\\RegistryPathFilesystemResource', $registry->resource('registry-path-filesystem'));
    }

    public function testAdminKitRegistryDiscoverPathsWithFilesystemTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $firstNamespace = $this->fixtureNamespace();
        $secondNamespace = $this->fixtureNamespace();
        $this->writeResource($first, $firstNamespace, 'RegistryFirstFilesystemResource', 'registry-first-filesystem');
        $this->writePage($second, $secondNamespace, 'RegistrySecondFilesystemPage', 'registry-second-filesystem');

        $registry = (new AdminKitRegistry())->discoverPaths([$first, $second]);

        self::assertSame($firstNamespace . '\\RegistryFirstFilesystemResource', $registry->resource('registry-first-filesystem'));
        self::assertSame($secondNamespace . '\\RegistrySecondFilesystemPage', $registry->page('registry-second-filesystem'));
    }

    public function testAdminKitRegistryDoesNotRegisterDuplicateResourceIdsTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $firstNamespace = $this->fixtureNamespace();
        $secondNamespace = $this->fixtureNamespace();
        $this->writeResource($first, $firstNamespace, 'FirstDuplicateResource', 'duplicate-resource');
        $this->writeResource($second, $secondNamespace, 'SecondDuplicateResource', 'duplicate-resource');

        $registry = (new AdminKitRegistry())->discoverPaths([$first, $second]);

        self::assertSame($firstNamespace . '\\FirstDuplicateResource', $registry->resource('duplicate-resource'));
    }

    public function testAdminKitRegistryDoesNotRegisterDuplicatePageIdsTest(): void
    {
        $first = $this->makeDirectory();
        $second = $this->makeDirectory();
        $firstNamespace = $this->fixtureNamespace();
        $secondNamespace = $this->fixtureNamespace();
        $this->writePage($first, $firstNamespace, 'FirstDuplicatePage', 'duplicate-page');
        $this->writePage($second, $secondNamespace, 'SecondDuplicatePage', 'duplicate-page');

        $registry = (new AdminKitRegistry())->discoverPaths([$first, $second]);

        self::assertSame($firstNamespace . '\\FirstDuplicatePage', $registry->page('duplicate-page'));
    }

    private function makeDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/adminkit_discovery_' . str_replace('.', '_', uniqid('', true));
        mkdir($directory, 0777, true);
        $this->directories[] = $directory;

        return $directory;
    }

    private function fixtureNamespace(): string
    {
        return __NAMESPACE__ . '\\Fixtures\\Case' . str_replace('.', '', uniqid('', true));
    }

    private function writeBaseResource(string $directory, string $namespace, string $class): void
    {
        file_put_contents($directory . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace {$namespace};

use MB\\Bitrix\\AdminKit\\Resource\\Resource;

abstract class {$class} extends Resource
{
}
PHP_CODE);
    }

    private function writeResource(
        string $directory,
        string $namespace,
        string $class,
        string $id,
        string $parent = 'Resource',
    ): void {
        $useResource = $parent === 'Resource' ? "use MB\\Bitrix\\AdminKit\\Resource\\Resource;\n" : '';
        file_put_contents($directory . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace {$namespace};

use MB\\Bitrix\\AdminKit\\Field\\Text;
{$useResource}
final class {$class} extends {$parent}
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

    private function writePageBase(string $directory, string $namespace, string $class): void
    {
        file_put_contents($directory . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace {$namespace};

use MB\\Bitrix\\AdminKit\\Pages\\CustomPage;

abstract class {$class} extends CustomPage
{
}
PHP_CODE);
    }

    private function writePage(
        string $directory,
        string $namespace,
        string $class,
        string $id,
        string $parent = 'CustomPage',
    ): void {
        $usePage = $parent === 'CustomPage' ? "use MB\\Bitrix\\AdminKit\\Pages\\CustomPage;\n" : '';
        file_put_contents($directory . '/' . $class . '.php', <<<PHP_CODE
<?php

declare(strict_types=1);

namespace {$namespace};

{$usePage}
final class {$class} extends {$parent}
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

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}

final class SpyClassFinder extends ClassFinder
{
    /** @var list<array{string,string,bool}> */
    public array $extendsCalls = [];

    public function extends(string $directory, string $baseClassFqcn, bool $deep = true): array
    {
        $this->extendsCalls[] = [$directory, $baseClassFqcn, $deep];

        return parent::extends($directory, $baseClassFqcn, $deep);
    }
}

abstract class BaseDiscoveryResource extends Resource
{
    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}
