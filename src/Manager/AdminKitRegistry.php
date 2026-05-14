<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Pages\AbstractPage;
use MB\Bitrix\AdminKit\Pages\CustomPage;
use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Pages\OptionsPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\Filesystem\Filesystem;
use ReflectionClass;

final class AdminKitRegistry
{
    /** @var array<string, class-string<Resource>> */
    private array $resources = [];

    /** @var array<string, class-string<AbstractPage>> */
    private array $pages = [];

    private bool $discovered = false;

    /** @param class-string<Resource> $resourceClass */
    public function registerResource(string $resourceClass): self
    {
        $this->resources[$resourceClass::getId()] = $resourceClass;
        $this->sort();

        return $this;
    }

    /** @param class-string<AbstractPage> $pageClass */
    public function registerPage(string $pageClass): self
    {
        $this->pages[$pageClass::getId()] = $pageClass;
        $this->sort();

        return $this;
    }

    public function discover(?string $libPath): self
    {
        if ($this->discovered) {
            return $this;
        }

        $this->discovered = true;
        if ($libPath === null) {
            return $this;
        }

        $this->discoverClasses($libPath, Resource::class, $this->resources);
        foreach ($this->pageBaseClasses() as $baseClass) {
            $this->discoverClasses($libPath, $baseClass, $this->pages);
        }
        $this->sort();

        return $this;
    }

    /** @return array<string, class-string<Resource>> */
    public function resources(): array
    {
        return AdminCollection::make($this->resources)->all();
    }

    /** @return array<string, class-string<AbstractPage>> */
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

    /** @param array<string, class-string> $registry */
    private function discoverClasses(string $libPath, string $baseClass, array &$registry): void
    {
        foreach (Filesystem::classFinder()->extends($libPath, $baseClass) as $item) {
            $class = $item['class'];
            if (!(new ReflectionClass($class))->isAbstract() && !isset($registry[$class::getId()])) {
                $registry[$class::getId()] = $class;
            }
        }
    }

    /** @return class-string[] */
    private function pageBaseClasses(): array
    {
        return [AbstractPage::class, OptionsPage::class, CustomPage::class, DashboardPage::class];
    }

    private function sort(): void
    {
        uasort($this->resources, static fn(string $a, string $b): int => $a::getSort() <=> $b::getSort());
        uasort($this->pages, static fn(string $a, string $b): int => $a::getSort() <=> $b::getSort());
    }
}
