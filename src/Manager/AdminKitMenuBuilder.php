<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

final class AdminKitMenuBuilder
{
    public function __construct(private AdminKitRegistry $registry, private string $baseUrl = '')
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function build(?PermissionContext $context = null): array
    {
        $entries = AdminCollection::make($this->entries($context))->all();
        $result = [];
        $groups = [];

        foreach ($entries as $entry) {
            $parentId = $entry['_parent'] ?? null;
            unset($entry['_parent']);

            if ($parentId !== null) {
                $groups[$parentId] ??= $this->makeGroup($parentId);
                $groups[$parentId]['items'][] = $entry;
                continue;
            }

            $result[] = $entry;
        }

        foreach ($groups as $group) {
            usort($group['items'], static fn (array $a, array $b): int => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));
            $result[] = $group;
        }

        usort($result, static fn (array $a, array $b): int => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function entries(?PermissionContext $context): array
    {
        $entries = [];
        foreach ($this->registry->resources() as $id => $class) {
            $resource = new $class();
            if (!$class::isVisibleInMenu() || !$resource->canView($context)) {
                continue;
            }

            $entries[] = [
                'text' => $resource->getTitle(),
                'title' => $resource->getTitle(),
                'url' => (new UrlGenerator($this->baseUrl))->resourceUrl($id, ['action' => $resource->hasCrud() ? 'list' : 'options']),
                'icon' => $class::getMenuIcon(),
                'sort' => $class::getSort(),
                'items_id' => AdminString::id('adminkit_menu', $id),
                '_parent' => $class::getParentMenuId() ?: (method_exists($resource, 'group') ? $resource->group() : null),
            ];
        }

        foreach ($this->registry->pages() as $id => $class) {
            $page = new $class();
            if (!$class::isVisibleInMenu() || !$page->canView($context ?? new PermissionContext())) {
                continue;
            }

            $entries[] = [
                'text' => $page->title(),
                'title' => $page->title(),
                'url' => (new UrlGenerator($this->baseUrl))->pageUrl($id),
                'icon' => $page->icon() ?? '',
                'sort' => $page->sort(),
                'items_id' => AdminString::id('adminkit_menu', $id),
                '_parent' => $class::getParentMenuId() ?: $page->group(),
            ];
        }

        usort($entries, static fn (array $a, array $b): int => ($a['sort'] ?? 500) <=> ($b['sort'] ?? 500));

        return $entries;
    }

    /** @return array<string, mixed> */
    private function makeGroup(string $parentId): array
    {
        $parentClass = $this->registry->resource($parentId) ?? $this->registry->page($parentId);
        $title = $parentClass !== null ? $parentClass::getTitle() : $parentId;
        $sort = $parentClass !== null ? $parentClass::getSort() : 500;

        return [
            'text' => $title,
            'title' => $title,
            'sort' => $sort,
            'url' => (new UrlGenerator($this->baseUrl))->pageUrl($parentId),
            'items_id' => AdminString::id('adminkit_group', $parentId),
            'more_url' => [],
            'items' => [],
        ];
    }
}
