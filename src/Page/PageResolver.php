<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\Page\PageContract as CorePageContract;
use MB\Bitrix\AdminKit\Contracts\Page\StandalonePageContract;
use MB\Bitrix\AdminKit\Exception\PageNotFoundException;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use RuntimeException;

final class PageResolver
{
    public function __construct(private readonly PageFactory $factory = new PageFactory())
    {
    }

    /**
     * @param class-string<StandalonePageContract> $pageClass
     * @param array<string,mixed> $params
     */
    public function resolve(string $pageClass, array $params = []): CorePageContract
    {
        if (!class_exists($pageClass)) {
            throw new RuntimeException(sprintf('Page class %s does not exist.', $pageClass));
        }

        if (!is_subclass_of($pageClass, StandalonePageContract::class)) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, StandalonePageContract::class));
        }

        $page = $this->factory->make($pageClass, null, null, $params);
        if (!$page instanceof StandalonePageContract) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, StandalonePageContract::class));
        }

        $context = new PermissionContext();
        if (!$page->canView($context)) {
            throw new PageNotFoundException(sprintf('Page "%s" is not accessible.', $page->id()));
        }

        return $page;
    }
}
