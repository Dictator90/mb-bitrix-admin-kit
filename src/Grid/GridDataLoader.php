<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Database\Performance\QueryPerformanceContext;
use MB\Bitrix\AdminKit\Support\AdminString;

final class GridDataLoader
{
    public function __construct(
        private readonly GridQueryBuilder $queryBuilder = new GridQueryBuilder(),
        private readonly QueryGuard $queryGuard = new QueryGuard(),
    ) {
    }

    public function load(
        ResourceContract $resource,
        Grid $grid,
        mixed $request = null,
        ?GridContext $context = null,
        ?IndexPageDefinitionContract $indexPage = null,
    ): ?QueryPerformanceContext {
        $dataManagerClass = $resource->getDataManagerClass();
        if (!$dataManagerClass) {
            return null;
        }

        $context ??= $this->makeContext($resource, $grid, $request);
        $params = $this->queryGuard->guardGridParams($this->queryBuilder->build($resource, $context, $indexPage), $context);

        $start = microtime(true);
        $countQueryUsed = false;
        $cacheUsed = false;

        if ($resource->useTotalCount($context)) {
            [$count, $cacheUsed] = $this->resolveTotalCount($resource, $dataManagerClass, $context, $params['filter'] ?? []);
            $countQueryUsed = !$cacheUsed;
            $grid->setTotalCount($count);
        }

        $result = $dataManagerClass::getList($params);
        $grid->setRawRows($result, $context, $indexPage);

        $performance = new QueryPerformanceContext(
            $resource,
            $context,
            $params,
            microtime(true) - $start,
            count($grid->getRows()),
            $countQueryUsed,
            $cacheUsed,
        );

        $this->debugQuery($performance);

        return $performance;
    }

    public function makeContext(ResourceContract $resource, Grid $grid, mixed $request = null): GridContext
    {
        return new GridContext(
            $resource,
            $grid->getId(),
            $grid->getFilterId(),
            [],
            [],
            $grid->getPagination()->getPageSize(),
            $grid->getPagination()->getCurrentPage(),
            $grid->getPagination()->getOffset(),
            $grid->getPagination()->getLimit(),
            $request,
        );
    }

    /**
     * @param class-string $dataManagerClass
     * @param array<string,mixed> $filter
     * @return array{0:int,1:bool}
     */
    private function resolveTotalCount(
        ResourceContract $resource,
        string $dataManagerClass,
        GridContext $context,
        array $filter,
    ): array {
        $ttl = $resource->countCacheTtl($context);
        if ($ttl <= 0) {
            return [(int)$dataManagerClass::getCount($filter), false];
        }

        $key = AdminString::cacheKey('adminkit_count', [
            'module' => 'mb.bitrix.adminkit',
            'resource' => $resource::getId(),
            'grid' => $context->gridId,
            'filter' => $filter,
            'user' => $this->currentUserId(),
        ]);
        $cached = ArrayTtlCache::get($key);
        if ($cached !== null) {
            return [(int)$cached, true];
        }

        $count = (int)$dataManagerClass::getCount($filter);
        ArrayTtlCache::set($key, $count, $ttl);

        return [$count, false];
    }

    private function debugQuery(QueryPerformanceContext $context): void
    {
        if (!$this->isDebugAllowed()) {
            return;
        }

        error_log('AdminKit ORM params: ' . json_encode([
            'resource' => $context->resource::getId(),
            'params' => $context->params,
            'executionTime' => $context->executionTime,
            'rowCount' => $context->rowCount,
            'countQueryUsed' => $context->countQueryUsed,
            'cacheUsed' => $context->cacheUsed,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function isDebugAllowed(): bool
    {
        if (!defined('ADMIN_KIT_DEBUG') || ADMIN_KIT_DEBUG !== true) {
            return false;
        }

        global $USER;
        return is_object($USER) && method_exists($USER, 'IsAdmin') && (bool)$USER->IsAdmin();
    }

    private function currentUserId(): mixed
    {
        global $USER;
        return is_object($USER) && method_exists($USER, 'GetID') ? $USER->GetID() : null;
    }
}
