<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

final class UrlGenerator
{
    public function __construct(private string $baseUrl = '')
    {
    }

    public static function forCurrentRequest(mixed $request): self
    {
        $uri = method_exists($request, 'getRequestUri') ? $request->getRequestUri() : ($_SERVER['REQUEST_URI'] ?? '');
        $parsed = parse_url($uri);
        parse_str($parsed['query'] ?? '', $query);
        $base = $parsed['path'] ?? '';
        if (isset($query['page'])) {
            $base .= '?' . http_build_query(['page' => $query['page']]);
        }
        return new self($base);
    }

    public function indexUrl(ResourceContract|string|null $resource = null): string
    {
        if ($resource instanceof ResourceContract) {
            return $this->resourceUrl($resource::getId());
        }
        if (is_string($resource)) {
            return $this->resourceUrl($resource);
        }
        return $this->baseUrl;
    }

    public function pageUrl(string $pageId, array $params = []): string
    {
        return $this->withQuery(['page' => $pageId] + $params);
    }
    public function resourceUrl(ResourceContract|string $resource, array $params = []): string
    {
        return $this->pageUrl($resource instanceof ResourceContract ? $resource::getId() : $resource, $params);
    }
    public function createUrl(array $extra = []): string
    {
        return $this->withQuery(['action' => 'add'] + $extra);
    }
    public function editUrl(mixed $id, array $extra = []): string
    {
        return $this->withQuery(['action' => 'edit', 'id' => $id] + $extra);
    }

    /** @param array<string,mixed> $extra */
    public function editResourceUrl(ResourceContract|string $resource, mixed $id, array $extra = []): string
    {
        $pageId = $resource instanceof ResourceContract ? $resource::getId() : $resource;

        return $this->withQuery(['page' => $pageId, 'action' => 'edit', 'id' => $id] + $extra, ['saved', 'IFRAME']);
    }
    public function detailUrl(mixed $id, array $extra = []): string
    {
        return $this->withQuery(['action' => 'detail', 'id' => $id] + $extra);
    }
    public function deleteUrl(mixed $id, array $extra = []): string
    {
        return $this->withQuery(['action' => 'delete', 'id' => $id, 'sessid' => (function_exists('bitrix_sessid') ? bitrix_sessid() : '')] + $extra);
    }
    public function actionUrl(string $action, array $params = []): string
    {
        return $this->withQuery(['action' => $action] + $params);
    }
    public function bulkActionUrl(string $action, array $params = []): string
    {
        return $this->actionUrl('bulk', ['bulk_action' => $action] + $params);
    }
    public function importUrl(array $params = []): string
    {
        return $this->actionUrl('import', $params);
    }
    public function exportUrl(array $params = []): string
    {
        return $this->actionUrl('export', $params);
    }
    public function endpointUrl(string $endpoint, array $params = []): string
    {
        return $this->actionUrl($endpoint, $params);
    }
    public function with(array $params): string
    {
        return $this->withQuery($params);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<int,string> $remove
     */
    private function withQuery(array $params, array $remove = []): string
    {
        $parsed = parse_url($this->baseUrl);
        parse_str($parsed['query'] ?? '', $query);
        foreach ($remove as $key) {
            unset($query[$key]);
        }
        $query = array_replace($query, $params);
        $path = $parsed['path'] ?? $this->baseUrl;
        return $path . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
