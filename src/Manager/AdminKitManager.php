<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Pages\AbstractPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\Contracts\Module\Entity as ModuleEntityContract;

/**
 * Per-module admin panel facade.
 *
 * The facade remains backward compatible while delegating v0.8.0 responsibilities
 * to AdminKitRegistry, AdminKitRouter, AdminKitMenuBuilder, and AdminKitRenderer.
 */
final class AdminKitManager
{
    private AdminKitRegistry $registry;
    private HttpRequest $request;

    public function __construct(private ModuleEntityContract $module)
    {
        $this->request = Context::getCurrent()->getRequest();
        $this->registry = new AdminKitRegistry();
    }

    /** @param class-string<Resource> $resourceClass */
    public function register(string $resourceClass): static
    {
        $this->registry->registerResource($resourceClass);

        return $this;
    }

    /** @param class-string<AbstractPage> $pageClass */
    public function registerPage(string $pageClass): static
    {
        $this->registry->registerPage($pageClass);

        return $this;
    }

    public function registry(): AdminKitRegistry
    {
        return $this->discover();
    }

    public function router(): AdminKitRouter
    {
        return new AdminKitRouter($this->discover(), $this->request);
    }

    public function menuBuilder(string $baseUrl = ''): AdminKitMenuBuilder
    {
        return new AdminKitMenuBuilder($this->discover(), $baseUrl !== '' ? $baseUrl : $this->resolveBaseUrl());
    }

    public function renderer(): AdminKitRenderer
    {
        return new AdminKitRenderer();
    }

    /** @return array<string, class-string<Resource>> */
    public function getResources(): array
    {
        return $this->discover()->resources();
    }

    /** @return array<string, class-string<AbstractPage>> */
    public function getPages(): array
    {
        return $this->discover()->pages();
    }

    public function getCurrentPage(): ResourcePage|AbstractPage|NotFoundPage
    {
        return $this->router()->currentPage();
    }

    /** @return array<int, array<string, mixed>> */
    public function getMenu(string $baseUrl = '', ?PermissionContext $context = null): array
    {
        return $this->menuBuilder($baseUrl)->build($context);
    }

    private function discover(): AdminKitRegistry
    {
        return $this->registry->discover($this->module->getLibPath());
    }

    private function resolveBaseUrl(): string
    {
        $uri = method_exists($this->request, 'getRequestUri') ? $this->request->getRequestUri() : ($_SERVER['REQUEST_URI'] ?? '');
        $pos = strpos($uri, '?');

        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }
}
