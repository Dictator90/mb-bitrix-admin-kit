<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

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
            return new self($module);
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
