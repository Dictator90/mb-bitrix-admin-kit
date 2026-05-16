<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\Page\PageContract as CorePageContract;
use MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract;
use MB\Bitrix\AdminKit\Contracts\Page\StandalonePageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use ReflectionClass;
use RuntimeException;

final class PageFactory
{
    /**
     * @param class-string<CorePageContract> $pageClass
     * @param array<string,mixed> $params
     */
    public function make(
        string $pageClass,
        ?ResourceContract $resource = null,
        mixed $id = null,
        array $params = [],
    ): CorePageContract {
        if (!class_exists($pageClass)) {
            throw new RuntimeException(sprintf('Page class %s does not exist.', $pageClass));
        }

        if (!is_subclass_of($pageClass, CorePageContract::class) && $pageClass !== CorePageContract::class) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, CorePageContract::class));
        }

        $reflection = new ReflectionClass($pageClass);
        if ($reflection->isAbstract()) {
            throw new RuntimeException(sprintf('Page class %s must not be abstract.', $pageClass));
        }

        if (is_subclass_of($pageClass, ResourcePageContract::class) || is_subclass_of($pageClass, ResourcePage::class)) {
            if ($resource === null) {
                throw new RuntimeException(sprintf('Resource is required to create page %s.', $pageClass));
            }

            $page = new $pageClass($resource, $id, $params);
        } elseif (is_subclass_of($pageClass, StandalonePageContract::class) || is_subclass_of($pageClass, StandalonePage::class)) {
            $page = new $pageClass($params);
        } elseif ($resource !== null) {
            $page = new $pageClass($resource, $id, $params);
        } else {
            $page = new $pageClass($params);
        }

        if (!$page instanceof CorePageContract) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, CorePageContract::class));
        }

        return $page;
    }
}
