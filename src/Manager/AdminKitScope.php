<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\Loader;
use RuntimeException;

final class AdminKitScope
{
    /** @param string[] $discoveryPaths */
    public function __construct(
        private string $scopeId,
        private array $discoveryPaths = []
    ) {
        $this->discoveryPaths = $this->filterPaths($discoveryPaths);
    }

    public static function fromModule(string|object $module): self
    {
        if (is_string($module)) {
            return self::fromModuleId($module);
        }

        $scopeId = self::stringFromMethod($module, 'getModuleId')
            ?? self::stringFromMethod($module, 'getId')
            ?? self::stringFromMethod($module, 'id')
            ?? self::stringFromProperty($module, 'moduleId')
            ?? self::stringFromProperty($module, 'id')
            ?? $module::class;

        $libPath = self::stringFromMethod($module, 'getLibPath') ?? self::stringFromProperty($module, 'libPath');
        if (! $libPath) {
            $basePath = self::stringFromMethod($module, 'getPath') ?? self::stringFromProperty($module, 'path');
            $libPath = $basePath . '/lib';
        }

        return new self($scopeId, $libPath !== null ? [$libPath] : []);
    }

    public static function fromModuleId(string $moduleId, string|array $discoveryPath = 'lib/Admin'): self
    {
        $modulePath = self::resolveModulePath($moduleId);
        $paths = [];

        foreach ((array) $discoveryPath as $path) {
            $paths[] = rtrim($modulePath, '/\\') . '/' . ltrim((string) $path, '/\\');
        }

        return new self($moduleId, $paths);
    }

    public static function resolveModulePath(string $moduleId): string
    {
        if (class_exists(Loader::class)) {
            foreach ([
                'modules/' . $moduleId . '/include.php',
                'modules/' . $moduleId . '/install/index.php',
            ] as $relative) {
                $path = Loader::getLocal($relative);
                if (is_string($path) && $path !== '') {
                    if (str_ends_with($path, '/include.php')) {
                        return dirname($path);
                    }

                    if (str_ends_with($path, '/install/index.php')) {
                        return dirname(dirname($path));
                    }
                }
            }
        }

        $documentRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

        foreach ([
            $documentRoot . '/local/modules/' . $moduleId,
            $documentRoot . '/bitrix/modules/' . $moduleId,
        ] as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        throw new RuntimeException(sprintf('Bitrix module [%s] was not found.', $moduleId));
    }

    public static function fromScope(string $scopeId): self
    {
        return new self($scopeId);
    }

    public static function fromDirectory(string $path, ?string $scopeId = null): self
    {
        return new self($scopeId ?? 'adminkit.local', [$path]);
    }

    /** @param string[] $paths */
    public static function fromDirectories(array $paths, ?string $scopeId = null): self
    {
        return new self($scopeId ?? 'adminkit.local', array_values($paths));
    }

    public function id(): string
    {
        return $this->scopeId;
    }

    public function scopeId(): string
    {
        return $this->scopeId;
    }

    /** @return string[] */
    public function discoveryPaths(): array
    {
        return $this->discoveryPaths;
    }

    public function withDiscoveryPath(string $path): self
    {
        return $this->withDiscoveryPaths([$path]);
    }

    /** @param string[] $paths */
    public function withDiscoveryPaths(array $paths): self
    {
        return new self($this->scopeId, array_merge($this->discoveryPaths, $paths));
    }

    private static function stringFromMethod(object $module, string $method): ?string
    {
        if (!method_exists($module, $method)) {
            return null;
        }

        $value = $module->{$method}();

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function stringFromProperty(object $module, string $property): ?string
    {
        if (!isset($module->{$property}) || !is_string($module->{$property}) || $module->{$property} === '') {
            return null;
        }

        return $module->{$property};
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    private function filterPaths(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            if (is_string($path) && trim($path) !== '') {
                $result[] = $path;
            }
        }

        return array_values(array_unique($result));
    }
}
