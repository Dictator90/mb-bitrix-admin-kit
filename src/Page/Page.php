<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Contracts\PageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;

abstract class Page implements PageContract
{
    protected ResourceContract $resource;
    protected HttpRequest $request;

    public function __construct(ResourceContract $resource)
    {
        $this->resource = $resource;
        $this->request = Context::getCurrent()->getRequest();
    }

    public function getResource(): ResourceContract
    {
        return $this->resource;
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
