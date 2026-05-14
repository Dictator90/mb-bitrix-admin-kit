<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Pages\AbstractPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class AdminKitRegistry
{
    /** @var array<string, class-string<Resource>> */
    private array $resources = [];

    /** @var array<string, class-string<AbstractPage>> */
    private array $pages = [];

    /** @var array<string, bool> */
    private array $discoveredPaths = [];

    /** @param class-string<Resource> $resourceClass */
    public function registerResource(string $resourceClass): self
    {
        if ($this->canRegister($resourceClass, Resource::class)) {
            $this->resources[$resourceClass::getId()] = $resourceClass;
            $this->sort();
        }

        return $this;
    }

    /** @param class-string<AbstractPage> $pageClass */
    public function registerPage(string $pageClass): self
    {
        if ($this->canRegister($pageClass, AbstractPage::class)) {
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
        foreach ($this->classesInPath($path) as $class) {
            if ($this->canRegister($class, Resource::class) && !isset($this->resources[$class::getId()])) {
                $this->resources[$class::getId()] = $class;
                continue;
            }

            if ($this->canRegister($class, AbstractPage::class) && !isset($this->pages[$class::getId()])) {
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

    /**
     * @return class-string[]
     */
    private function classesInPath(string $path): array
    {
        $classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->classFromFile($file->getPathname());
            if ($class === null) {
                continue;
            }

            if (!class_exists($class)) {
                require_once $file->getPathname();
            }

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        return array_values(array_unique($classes));
    }

    private function classFromFile(string $file): ?string
    {
        $tokens = token_get_all((string)file_get_contents($file));
        $namespace = '';
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = $this->readName($tokens, $i + 1);
                continue;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS && !$this->isAnonymousClass($tokens, $i)) {
                $class = $this->readName($tokens, $i + 1);

                return $class !== '' ? ltrim($namespace . '\\' . $class, '\\') : null;
            }
        }

        return null;
    }

    /** @param array<int, mixed> $tokens */
    private function readName(array $tokens, int $index): string
    {
        $name = '';
        $count = count($tokens);
        for ($i = $index; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true)) {
                $name .= $token[1];
                continue;
            }
            if ($token === '\\') {
                $name .= '\\';
                continue;
            }
            if ($name === '' && is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            break;
        }

        return $name;
    }

    /** @param array<int, mixed> $tokens */
    private function isAnonymousClass(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) && $token[0] === T_NEW;
        }

        return false;
    }

    private function canRegister(string $class, string $baseClass): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, $baseClass)) {
            return false;
        }

        return !(new ReflectionClass($class))->isAbstract();
    }

    private function sort(): void
    {
        uasort($this->resources, static fn (string $a, string $b): int => $a::getSort() <=> $b::getSort());
        uasort($this->pages, static fn (string $a, string $b): int => $a::getSort() <=> $b::getSort());
    }
}
