<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Pages\AbstractPage;
use MB\Bitrix\AdminKit\Pages\CustomPage;
use MB\Bitrix\AdminKit\Pages\OptionsPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\Contracts\Module\Entity as ModuleEntityContract;
use MB\Bitrix\Filesystem\Filesystem;
use ReflectionClass;

/**
 * Per-module admin panel manager.
 *
 * Discovers and routes both Resource classes (ORM/CRUD) and
 * standalone AbstractPage classes (OptionsPage, CustomPage, etc.).
 *
 * Usage in admin file:
 *   module('my.module')->adminKit()->getCurrentPage()->render();
 *
 * Usage in menu.php:
 *   module('my.module')->adminKit()->getMenu('/bitrix/admin/my_settings.php');
 */
final class AdminKitManager
{
    private const PAGE_PARAM = 'page';

    private HttpRequest $request;

    /** @var array<string, class-string<Resource>> id => class */
    private array $resources = [];

    /** @var array<string, class-string<AbstractPage>> id => class */
    private array $pages = [];

    private bool $discovered = false;

    public function __construct(private ModuleEntityContract $module)
    {
        $this->request = Context::getCurrent()->getRequest();
    }

    // ── Registration ─────────────────────────────────────────────────────

    /**
     * Explicitly register a Resource class.
     *
     * @param class-string<Resource> $resourceClass
     */
    public function register(string $resourceClass): static
    {
        $this->resources[$resourceClass::getId()] = $resourceClass;

        return $this;
    }

    /**
     * Explicitly register a standalone AbstractPage class.
     *
     * @param class-string<AbstractPage> $pageClass
     */
    public function registerPage(string $pageClass): static
    {
        $this->pages[$pageClass::getId()] = $pageClass;

        return $this;
    }

    // ── Discovery ────────────────────────────────────────────────────────

    private function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;
        $libPath = $this->module->getLibPath();
        if ($libPath === null) {
            return;
        }

        // Discover Resources
        $this->discoverClasses($libPath, Resource::class, $this->resources);

        // Discover standalone Pages: search each known AdminKit abstract base.
        // classFinder only matches direct parent, so we enumerate all abstract
        // intermediate classes (AbstractPage, OptionsPage, CustomPage, …).
        // User classes extend OptionsPage/CustomPage — not AbstractPage directly.
        foreach ($this->pageBaseClasses() as $base) {
            $this->discoverClasses($libPath, $base, $this->pages);
        }

        uasort($this->resources, static fn($a, $b) => $a::getSort() <=> $b::getSort());
        uasort($this->pages, static fn($a, $b) => $a::getSort() <=> $b::getSort());
    }

    /**
     * Find all non-abstract subclasses of $baseClass in $libPath and add them to $registry.
     *
     * classFinder()->extends() only matches DIRECT parent, so the caller must enumerate
     * all relevant abstract bases (see pageBaseClasses() / discover()).
     *
     * @param array<string, class-string> $registry
     */
    private function discoverClasses(string $libPath, string $baseClass, array &$registry): void
    {
        foreach (Filesystem::classFinder()->extends($libPath, $baseClass) as $item) {
            $class = $item['class'];

            if (!(new ReflectionClass($class))->isAbstract()) {
                if (!isset($registry[$class::getId()])) {
                    $registry[$class::getId()] = $class;
                }
            }
        }
    }

    /**
     * All AdminKit abstract page classes that user code can extend directly.
     * When a new abstract page type is added here, it is picked up automatically.
     *
     * @return class-string[]
     */
    private function pageBaseClasses(): array
    {
        return [
            AbstractPage::class,
            OptionsPage::class,
            CustomPage::class,
        ];
    }

    /** @return array<string, class-string<Resource>> */
    public function getResources(): array
    {
        $this->discover();

        return $this->resources;
    }

    /** @return array<string, class-string<AbstractPage>> */
    public function getPages(): array
    {
        $this->discover();

        return $this->pages;
    }

    // ── Routing ──────────────────────────────────────────────────────────

    /**
     * Return the renderable for the current request.
     *
     * Routes ?page= to either a ResourcePage (wraps Resource via mb:admin.resource component)
     * or a standalone AbstractPage instance.
     * Falls back to the first registered item (resources first, then pages).
     * Returns NotFoundPage if nothing matches.
     */
    public function getCurrentPage(): ResourcePage|AbstractPage|NotFoundPage
    {
        $this->discover();

        $pageId = $this->request->getQuery(self::PAGE_PARAM);

        if ($pageId) {
            // Standalone Page takes priority when IDs clash
            if (isset($this->pages[$pageId])) {
                return new $this->pages[$pageId]();
            }

            if (isset($this->resources[$pageId])) {
                return new ResourcePage(new $this->resources[$pageId]());
            }

            return new NotFoundPage();
        }

        // No ?page= — show first item (resources first, then pages)
        if (!empty($this->resources)) {
            $firstClass = reset($this->resources);
            return new ResourcePage(new $firstClass());
        }

        if (!empty($this->pages)) {
            $firstClass = reset($this->pages);
            return new $firstClass();
        }

        return new NotFoundPage();
    }

    // ── Menu ─────────────────────────────────────────────────────────────

    /**
     * Build a Bitrix admin sidebar menu array from all visible Resources and Pages.
     *
     * @param string $baseUrl Admin file URL, e.g. '/bitrix/admin/my_settings.php'
     * @return array<int, array<string, mixed>>
     */
    public function getMenu(string $baseUrl = ''): array
    {
        $this->discover();

        if ($baseUrl === '') {
            $baseUrl = $this->resolveBaseUrl();
        }

        $result = [];
        $groups = [];

        // Merge resources and pages into one sorted collection
        $all = $this->buildMenuEntries($baseUrl);

        foreach ($all as $entry) {
            $parentId = $entry['_parent'] ?? null;
            unset($entry['_parent']);

            if ($parentId !== null) {
                if (!isset($groups[$parentId])) {
                    $groups[$parentId] = $this->makeGroup($parentId, $baseUrl);
                }
                $groups[$parentId]['items'][] = $entry;
            } else {
                $result[] = $entry;
            }
        }

        foreach ($groups as $group) {
            usort($group['items'], static fn($a, $b) => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));
            $result[] = $group;
        }

        usort($result, static fn($a, $b) => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));

        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** @return array[] Combined, sort-merged menu entries for resources and pages. */
    private function buildMenuEntries(string $baseUrl): array
    {
        $entries = [];

        foreach ($this->resources as $id => $class) {
            if (!$class::isVisibleInMenu()) {
                continue;
            }

            $action = (new $class())->hasCrud() ? 'list' : 'options';
            $sep    = str_contains($baseUrl, '?') ? '&' : '?';
            $url    = $baseUrl . $sep . self::PAGE_PARAM . '=' . urlencode($id) . '&action=' . $action;

            $entries[] = [
                'text'    => (new $class())->getTitle(),
                'title'   => (new $class())->getTitle(),
                'url'     => $url,
                'icon'    => $class::getMenuIcon(),
                'sort'    => $class::getSort(),
                '_parent' => $class::getParentMenuId(),
            ];
        }

        foreach ($this->pages as $id => $class) {
            if (!$class::isVisibleInMenu()) {
                continue;
            }

            $sep = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $baseUrl . $sep . self::PAGE_PARAM . '=' . urlencode($id);

            $entries[] = [
                'text'    => $class::getTitle(),
                'title'   => $class::getTitle(),
                'url'     => $url,
                'icon'    => $class::getMenuIcon(),
                'sort'    => $class::getSort(),
                '_parent' => $class::getParentMenuId(),
            ];
        }

        usort($entries, static fn($a, $b) => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));

        return $entries;
    }

    private function makeGroup(string $parentId, string $baseUrl): array
    {
        // Try resource first, then page
        $parentClass = $this->resources[$parentId] ?? $this->pages[$parentId] ?? null;

        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        $url = $baseUrl . $sep . self::PAGE_PARAM . '=' . urlencode($parentId);

        return [
            'text'     => $parentClass ? $parentClass::getTitle() : $parentId,
            'sort'     => $parentClass ? $parentClass::getSort() : 500,
            'url'      => $url,
            'items_id' => 'adminkit_group_' . $parentId,
            'more_url' => [],
            'items'    => [],
        ];
    }

    private function resolveBaseUrl(): string
    {
        $uri = $this->request->getRequestUri();
        $pos = strpos($uri, '?');

        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }
}
