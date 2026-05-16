<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Support\AdminString;

final class AdminKitRouter
{
    public const PAGE_PARAM = 'page';
    public const RESOURCE_PARAM = 'admin_resource';
    public const ADMIN_PAGE_PARAM = 'admin_page';
    public const ACTION_PARAM = 'action';

    public function __construct(private AdminKitRegistry $registry, private HttpRequest $request)
    {
    }

    public function currentPage(): ResourcePage|StandalonePage|NotFoundPage
    {
        $queryPage = method_exists($this->request, 'getQuery') ? $this->request->getQuery(self::PAGE_PARAM) : null;
        $queryResource = method_exists($this->request, 'getQuery') ? $this->request->getQuery(self::RESOURCE_PARAM) : null;
        $pageId = (string)($queryResource ?: $this->request->get(self::RESOURCE_PARAM) ?: $queryPage ?: $this->request->get(self::PAGE_PARAM) ?: '');

        if ($pageId !== '') {
            $pageId = AdminString::slug($pageId);
            $pageClass = $this->registry->page($pageId);
            if ($pageClass !== null) {
                return new $pageClass();
            }

            $resourceClass = $this->registry->resource($pageId);
            if ($resourceClass !== null) {
                return new ResourcePage(new $resourceClass());
            }

            return new NotFoundPage();
        }

        $resourceClass = $this->registry->firstResource();
        if ($resourceClass !== null) {
            return new ResourcePage(new $resourceClass());
        }

        $pageClass = $this->registry->firstPage();
        if ($pageClass !== null) {
            return new $pageClass();
        }

        return new NotFoundPage();
    }

    public function action(): string
    {
        return (string)($this->request->get(self::ACTION_PARAM) ?: $this->request->getPost(self::ACTION_PARAM) ?: 'list');
    }

    public function routeKey(): string
    {
        return AdminString::cacheKey('adminkit_route', [
            'page' => $this->request->get(self::PAGE_PARAM),
            'admin_resource' => $this->request->get(self::RESOURCE_PARAM),
            'admin_page' => $this->request->get(self::ADMIN_PAGE_PARAM),
            'action' => $this->action(),
            'id' => $this->request->get('id'),
        ]);
    }
}
