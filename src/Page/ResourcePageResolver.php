<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Exception\PageNotFoundException;

final class ResourcePageResolver
{
    public function __construct(private readonly PageFactory $factory = new PageFactory())
    {
    }

    /** @param array<string,mixed> $params */
    public function resolve(ResourceContract $resource, string $pageName, mixed $id = null, array $params = []): PageContract
    {
        foreach ($resource->pages() as $pageClass) {
            if (!is_string($pageClass) || !is_subclass_of($pageClass, PageContract::class)) {
                continue;
            }

            if ($pageClass::pageName() === $pageName) {
                return $this->factory->make($pageClass, $resource, $id, $params);
            }
        }

        throw new PageNotFoundException(sprintf(
            'Page "%s" is not registered for resource %s.',
            $pageName,
            $resource::class,
        ));
    }
}
