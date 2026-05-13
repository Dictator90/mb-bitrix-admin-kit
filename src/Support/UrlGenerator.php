<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

final class UrlGenerator
{
    public function __construct(private string $baseUrl = '') {}

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
            return $this->withQuery(['page' => $resource::getId()]);
        }
        if (is_string($resource)) {
            return $this->withQuery(['page' => $resource]);
        }
        return $this->baseUrl;
    }

    public function createUrl(array $extra = []): string { return $this->withQuery(['action' => 'add'] + $extra); }
    public function editUrl(mixed $id, array $extra = []): string { return $this->withQuery(['action' => 'edit', 'id' => $id] + $extra); }
    public function deleteUrl(mixed $id, array $extra = []): string { return $this->withQuery(['action' => 'delete', 'id' => $id, 'sessid' => (function_exists('bitrix_sessid') ? bitrix_sessid() : '')] + $extra); }
    public function actionUrl(string $action, array $params = []): string { return $this->withQuery(['action' => $action] + $params); }

    private function withQuery(array $params): string
    {
        $parsed = parse_url($this->baseUrl);
        parse_str($parsed['query'] ?? '', $query);
        $query = array_replace($query, $params);
        $path = $parsed['path'] ?? $this->baseUrl;
        return $path . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
