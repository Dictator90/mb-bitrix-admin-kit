<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use MB\Bitrix\AdminKit\Support\AdminCollection;

class RelationResolver
{
    /** @var array<string,array<string,array<string,mixed>>> */
    private array $cache = [];

    /** @var array<string,int> */
    private array $queryCounts = [];

    /**
     * @param class-string $dataManager
     * @param iterable<mixed> $ids
     * @param string[] $select
     * @return array<string,array<string,mixed>>
     */
    public function preload(string $dataManager, iterable $ids, string $primary = 'ID', array $select = ['*']): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('strval', AdminCollection::make($ids)->all()),
            static fn(string $id): bool => $id !== ''
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $bucket = $this->bucketKey($dataManager, $primary, $select);
        $this->cache[$bucket] ??= [];
        $missing = array_values(array_filter(
            $normalizedIds,
            fn(string $id): bool => !array_key_exists($id, $this->cache[$bucket])
        ));

        if ($missing !== []) {
            $this->queryCounts[$bucket] = ($this->queryCounts[$bucket] ?? 0) + 1;
            $result = $dataManager::getList([
                'filter' => ['@' . $primary => $missing],
                'select' => $select,
            ]);

            while ($row = $result->fetch()) {
                $this->cache[$bucket][(string)$row[$primary]] = $row;
            }

            foreach ($missing as $id) {
                $this->cache[$bucket][$id] ??= [];
            }
        }

        $rows = [];
        foreach ($normalizedIds as $id) {
            if ($this->cache[$bucket][$id] !== []) {
                $rows[$id] = $this->cache[$bucket][$id];
            }
        }

        return $rows;
    }

    /** @param class-string $dataManager */
    public function resolve(string $dataManager, mixed $id, string $primary = 'ID', array $select = ['*']): ?array
    {
        $rows = $this->preload($dataManager, [$id], $primary, $select);

        return $rows[(string)$id] ?? null;
    }

    /** @return array<string,int> */
    public function getQueryCounts(): array
    {
        return $this->queryCounts;
    }

    /** @param string[] $select */
    private function bucketKey(string $dataManager, string $primary, array $select): string
    {
        sort($select);

        return $dataManager . '|' . $primary . '|' . implode(',', $select);
    }
}
