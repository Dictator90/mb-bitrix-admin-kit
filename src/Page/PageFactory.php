<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use ReflectionClass;
use RuntimeException;

final class PageFactory
{
    /**
     * @param class-string<PageContract> $pageClass
     * @param array<string,mixed> $params
     */
    public function make(string $pageClass, ResourceContract $resource, mixed $id = null, array $params = []): PageContract
    {
        if (!class_exists($pageClass)) {
            throw new RuntimeException(sprintf('Page class %s does not exist.', $pageClass));
        }

        if (!is_subclass_of($pageClass, PageContract::class)) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, PageContract::class));
        }

        $reflection = new ReflectionClass($pageClass);
        if ($reflection->isAbstract()) {
            throw new RuntimeException(sprintf('Page class %s must not be abstract.', $pageClass));
        }

        $page = new $pageClass($resource, $id, $params);
        if (!$page instanceof PageContract) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, PageContract::class));
        }

        return $page;
    }
}
