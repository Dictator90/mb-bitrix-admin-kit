<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use LogicException;
use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Exception\PageNotFoundException;
use RuntimeException;

final class ResourcePageResolver
{
    public function __construct(private readonly PageFactory $factory = new PageFactory())
    {
    }

    /** @param array<string,mixed> $params */
    public function resolve(ResourceContract $resource, string $pageName, mixed $id = null, array $params = []): PageContract
    {
        $byName = [];

        foreach ($resource->pages() as $pageClass) {
            if (!is_string($pageClass)) {
                throw new LogicException(sprintf(
                    'Resource %s pages() must return class name strings, got %s.',
                    $resource::class,
                    get_debug_type($pageClass),
                ));
            }

            if (!class_exists($pageClass)) {
                throw new RuntimeException(sprintf('Page class %s does not exist.', $pageClass));
            }

            if (!is_subclass_of($pageClass, PageContract::class)) {
                throw new RuntimeException(sprintf(
                    'Page class %s must implement %s.',
                    $pageClass,
                    PageContract::class,
                ));
            }

            $name = $pageClass::pageName();
            if (isset($byName[$name])) {
                throw new LogicException(sprintf(
                    'Duplicate page name "%s" in resource %s pages(): %s and %s.',
                    $name,
                    $resource::class,
                    $byName[$name],
                    $pageClass,
                ));
            }

            $byName[$name] = $pageClass;
        }

        if (!isset($byName[$pageName])) {
            throw new PageNotFoundException(sprintf(
                'Page "%s" is not registered for resource %s.',
                $pageName,
                $resource::class,
            ));
        }

        return $this->factory->make($byName[$pageName], $resource, $id, $params);
    }
}
