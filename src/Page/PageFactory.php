<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use RuntimeException;

final class PageFactory
{
    /**
     * @param class-string<PageContract> $pageClass
     * @param array<string,mixed> $params
     */
    public function make(string $pageClass, ResourceContract $resource, mixed $id = null, array $params = []): PageContract
    {
        $page = new $pageClass($resource, $id, $params);
        if (!$page instanceof PageContract) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, PageContract::class));
        }

        return $page;
    }
}
