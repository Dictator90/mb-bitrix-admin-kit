<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Security\PermissionContext;

abstract class Page implements PageContract
{
    protected HttpRequest $request;

    /** @param array<string,mixed> $params */
    public function __construct(
        protected ResourceContract $resource,
        protected mixed $id = null,
        protected array $params = [],
    ) {
        $this->request = Context::getCurrent()->getRequest();
    }

    public static function pageName(): string
    {
        return 'page';
    }

    public function resource(): ResourceContract
    {
        return $this->resource;
    }

    public function getResource(): ResourceContract
    {
        return $this->resource();
    }

    public function title(): string
    {
        return $this->resource->getTitle();
    }

    public function canView(?PermissionContext $context = null): bool
    {
        return $this->resource->canView($context);
    }

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }

    protected function redirect(string $url): void
    {
        LocalRedirect($url);
    }
}
