<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Discovery\ClassDiscovery;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class AdminKitRegistry
{
    private ClassDiscovery $discovery;

    /** @var array<string, class-string<Resource>> */
    private array $resources = [];

    /** @var array<string, class-string<StandalonePage>> */
    private array $pages = [];

    /** @var array<string, bool> */
    private array $discoveredPaths = [];

    public function __construct(?ClassDiscovery $discovery = null)
    {
        $this->discovery = $discovery ?? new ClassDiscovery();
    }

    /** @param class-string<Resource> $resourceClass */
    public function registerResource(string $resourceClass): self
    {
        if ($this->canRegister($resourceClass, Resource::class) && !isset($this->resources[$resourceClass::getId()])) {
            $this->resources[$resourceClass::getId()] = $resourceClass;
            $this->sort();
        }

        return $this;
    }

    /** @param class-string<StandalonePage> $pageClass */
    public function registerPage(string $pageClass): self
    {
        if ($this->canRegisterPage($pageClass) && !isset($this->pages[$pageClass::getId()])) {
            $this->pages[$pageClass::getId()] = $pageClass;
            $this->sort();
        }

        return $this;
    }

    public function discoverPath(string $path): self
    {
        $path = $this->normalizePath($path);
        if ($path === '' || isset($this->discoveredPaths[$path]) || !is_dir($path)) {
            return $this;
        }

        $this->discoveredPaths[$path] = true;

        foreach ($this->discovery->resourcesIn($path) as $class) {
            if (!isset($this->resources[$class::getId()])) {
                $this->resources[$class::getId()] = $class;
            }
        }

        foreach ($this->discovery->standalonePagesIn($path) as $class) {
            if (!isset($this->pages[$class::getId()])) {
                $this->pages[$class::getId()] = $class;
            }
        }

        $this->sort();

        return $this;
    }

    /** @param string[] $paths */
    public function discoverPaths(array $paths): self
    {
        foreach ($paths as $path) {
            if (is_string($path)) {
                $this->discoverPath($path);
            }
        }
        $this->sort();

        return $this;
    }

    /** @return array<string, class-string<Resource>> */
    public function resources(): array
    {
        return AdminCollection::make($this->resources)->all();
    }

    /** @return array<string, class-string<StandalonePage>> */
    public function pages(): array
    {
        return AdminCollection::make($this->pages)->all();
    }

    public function resource(string $id): ?string
    {
        return $this->resources[$id] ?? null;
    }

    public function page(string $id): ?string
    {
        return $this->pages[$id] ?? null;
    }

    public function firstResource(): ?string
    {
        return reset($this->resources) ?: null;
    }

    public function firstPage(): ?string
    {
        return reset($this->pages) ?: null;
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        $realPath = realpath($path);
        if (is_string($realPath)) {
            return str_replace('\\', '/', $realPath);
        }

        return rtrim($path, '/');
    }

    private function canRegisterPage(string $class): bool
    {
        if (!$class::isStandalone()) {
            return false;
        }

        return $this->canRegister($class, StandalonePage::class);
    }

    private function canRegister(string $class, string $baseClass): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, $baseClass)) {
            return false;
        }

        return !(new \ReflectionClass($class))->isAbstract();
    }

    private function sort(): void
    {
        uasort($this->resources, static fn (string $a, string $b): int => $a::getSort() <=> $b::getSort());
        uasort($this->pages, static fn (string $a, string $b): int => $a::getSort() <=> $b::getSort());
    }
}
