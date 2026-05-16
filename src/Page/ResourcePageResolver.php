<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use LogicException;
use MB\Bitrix\AdminKit\Contracts\Page\PageContract as CorePageContract;
use MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Exception\PageNotFoundException;

final class ResourcePageResolver
{
    public function __construct(private readonly PageFactory $factory = new PageFactory())
    {
    }

    /** @param array<string,mixed> $params */
    public function resolve(ResourceContract $resource, string $pageName, mixed $id = null, array $params = []): ResourcePageContract
    {
        $pages = Pages::make($resource->pages())->setResource($resource);
        $page = $pages->findByName($pageName);

        if ($page === null) {
            throw new PageNotFoundException(sprintf(
                'Page "%s" is not registered for resource %s.',
                $pageName,
                $resource::class,
            ));
        }

        if ($page instanceof ResourcePageContract) {
            $page->setResource($resource);
            $page->setContext($id, $params);
        }

        if (!$page instanceof ResourcePageContract) {
            throw new LogicException(sprintf(
                'Page "%s" must implement %s for resource %s.',
                $pageName,
                ResourcePageContract::class,
                $resource::class,
            ));
        }

        return $page;
    }
}
