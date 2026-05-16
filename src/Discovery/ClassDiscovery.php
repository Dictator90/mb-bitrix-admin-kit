<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Discovery;

use Bitrix\Main\ORM\Data\DataManager;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Filesystem\Filesystem;
use MB\Filesystem\Finder\ClassFinder;
use ReflectionClass;

final class ClassDiscovery
{
    public function __construct(
        private readonly ClassFinder $classFinder = new ClassFinder(new Filesystem()),
    ) {
    }

    /** @return list<class-string<Resource<DataManager>>> */
    public function resourcesIn(string $path): array
    {
        return $this->subclassesOf($path, Resource::class);
    }

    /** @return list<class-string<StandalonePage>> */
    public function standalonePagesIn(string $path): array
    {
        $pages = [];

        foreach ($this->subclassesOf($path, StandalonePage::class) as $class) {
            if ($class::isStandalone()) {
                $pages[$class] = $class;
            }
        }

        return array_values($pages);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $baseClass
     * @return list<class-string<T>>
     */
    private function subclassesOf(string $path, string $baseClass): array
    {
        if ($path === '' || !is_dir($path)) {
            return [];
        }

        $classes = [];

        foreach ($this->loadClasses($this->classMetadata($path, $baseClass)) as $class) {
            if (!$this->isConcreteSubclassOf($class, $baseClass)) {
                continue;
            }

            $classes[$class] = $class;
        }

        return array_values($classes);
    }


    /**
     * @param class-string $baseClass
     * @return array<int,array<string,mixed>>
     */
    private function classMetadata(string $path, string $baseClass): array
    {
        $metadata = $this->classFinder->extends($path, $baseClass, true);

        $allMetadata = $this->allClassMetadata($path);
        $byClass = $this->metadataByClass($allMetadata);

        foreach ($allMetadata as $classInfo) {
            if ($this->metadataMayDescribeSubclass($classInfo, $baseClass, $byClass)) {
                $metadata[] = $classInfo;
            }
        }

        return $this->uniqueMetadata($metadata);
    }


    /**
     * @param array<int,array<string,mixed>> $metadata
     * @return array<string,array<string,mixed>>
     */
    private function metadataByClass(array $metadata): array
    {
        $byClass = [];
        foreach ($metadata as $classInfo) {
            $class = $classInfo['class'] ?? null;
            if (!is_string($class) || $class === '') {
                continue;
            }

            $byClass[$this->normalizeClassName($class)] = $classInfo;
        }

        return $byClass;
    }

    /**
     * @param array<string,mixed> $classInfo
     * @param class-string $baseClass
     * @param array<string,array<string,mixed>> $byClass
     */
    private function metadataMayDescribeSubclass(array $classInfo, string $baseClass, array $byClass): bool
    {
        $target = $this->normalizeClassName($baseClass);
        $parent = $classInfo['extends'] ?? null;
        $visited = [];

        while (is_string($parent) && $parent !== '') {
            $normalizedParent = $this->normalizeClassName($parent);
            if ($normalizedParent === $target) {
                return true;
            }

            if (isset($visited[$normalizedParent])) {
                return false;
            }

            $visited[$normalizedParent] = true;
            $parentInfo = $byClass[$normalizedParent] ?? null;
            if ($parentInfo !== null) {
                $parent = $parentInfo['extends'] ?? null;
                continue;
            }

            return class_exists($parent) && is_subclass_of($parent, $baseClass);
        }

        return false;
    }

    /** @return array<int,array<string,mixed>> */
    private function allClassMetadata(string $path): array
    {
        if (method_exists($this->classFinder, 'classes')) {
            /** @var array<int,array<string,mixed>> $classes */
            $classes = $this->classFinder->classes($path);

            return $classes;
        }

        $method = new \ReflectionMethod(ClassFinder::class, 'parseDirectoryClasses');
        $method->setAccessible(true);

        /** @var array<int,array<string,mixed>> $classes */
        $classes = $method->invoke($this->classFinder, $path);

        return $classes;
    }

    /**
     * @param array<int,array<string,mixed>> $metadata
     * @return array<int,array<string,mixed>>
     */
    private function uniqueMetadata(array $metadata): array
    {
        $unique = [];
        foreach ($metadata as $classInfo) {
            $class = $classInfo['class'] ?? null;
            if (!is_string($class) || $class === '') {
                continue;
            }

            $unique[$this->normalizeClassName($class)] = $classInfo;
        }

        return array_values($unique);
    }

    /**
     * @param array<int,array<string,mixed>> $metadata
     * @return list<class-string>
     */
    private function loadClasses(array $metadata): array
    {
        $byClass = $this->metadataByClass($metadata);

        $loaded = [];
        $visiting = [];
        foreach ($metadata as $classInfo) {
            $this->loadClass($classInfo, $byClass, $loaded, $visiting);
        }

        return array_values($loaded);
    }

    /**
     * @param array<string,mixed> $classInfo
     * @param array<string,array<string,mixed>> $byClass
     * @param array<class-string,class-string> $loaded
     * @param array<string,bool> $visiting
     */
    private function loadClass(array $classInfo, array $byClass, array &$loaded, array &$visiting): void
    {
        $class = $classInfo['class'] ?? null;
        if (!is_string($class) || $class === '') {
            return;
        }

        $normalizedClass = $this->normalizeClassName($class);
        if (isset($visiting[$normalizedClass])) {
            return;
        }

        if (class_exists($class)) {
            /** @var class-string $class */
            $loaded[$class] = $class;
            return;
        }

        $visiting[$normalizedClass] = true;

        $parent = $classInfo['extends'] ?? null;
        if (is_string($parent) && $parent !== '') {
            $parentInfo = $byClass[$this->normalizeClassName($parent)] ?? null;
            if ($parentInfo !== null) {
                $this->loadClass($parentInfo, $byClass, $loaded, $visiting);
            }
        }

        unset($visiting[$normalizedClass]);

        if (!class_exists($class, false)) {
            $file = $classInfo['file'] ?? null;
            if (is_string($file) && is_file($file)) {
                require_once $file;
            }
        }

        // @phpstan-ignore-next-line require_once loads the parsed class when it is not autoloadable.
        if (class_exists($class)) {
            /** @var class-string $class */
            $loaded[$class] = $class;
        }
    }

    /** @param class-string $baseClass */
    private function isConcreteSubclassOf(string $class, string $baseClass): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, $baseClass)) {
            return false;
        }

        return !(new ReflectionClass($class))->isAbstract();
    }

    private function normalizeClassName(string $class): string
    {
        return strtolower(ltrim($class, '\\'));
    }
}
