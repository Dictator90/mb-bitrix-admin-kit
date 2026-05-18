<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

final class DiscoveryConfig
{
    /** @var string[] */
    private array $paths = [];

    public function addPath(string $path): self
    {
        $path = $this->normalizePath($path);
        if ($path === '' || !is_dir($path) || in_array($path, $this->paths, true)) {
            return $this;
        }

        $this->paths[] = $path;

        return $this;
    }

    /** @param string[] $paths */
    public function addPaths(array $paths): self
    {
        foreach ($paths as $path) {
            if (is_string($path)) {
                $this->addPath($path);
            }
        }

        return $this;
    }

    /** @return string[] */
    public function paths(): array
    {
        return $this->paths;
    }

    public function isEmpty(): bool
    {
        return $this->paths === [];
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
}
