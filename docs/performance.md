# Query performance tools

v0.6.0 adds small, opt-in performance controls for resource grids and field lookups.

## Disable total count

Heavy `getCount()` queries can be disabled per resource:

```php
public function useTotalCount(GridContext $context): bool
{
    return false;
}
```

When disabled, the grid data query still runs, but the exact total count query is skipped.

## Count cache

Count queries can be cached per grid/filter/user key:

```php
public function countCacheTtl(GridContext $context): int
{
    return 300;
}
```

Cache keys are generated through `AdminString::cacheKey()` from module id, resource id, grid id, filter hash data, and current user id.

## Select options cache

`Select` supports cached option providers:

```php
Select::make('Статус', 'STATUS')
    ->options(fn () => ['Y' => 'Active', 'N' => 'Inactive'])
    ->cache(3600);
```

Static arrays and callable option providers are both supported.

## Lookup cache

`RelationResolver` keeps request-level cache and can also use a TTL cache:

```php
$resolver = (new RelationResolver())->cache(3600);
$resolver->preload(ProductTable::class, [1, 2, 3], 'ID', ['ID', 'NAME']);
```

Use `preload()` to batch relation labels and avoid N+1 queries.

## Query guard and max page size

`QueryGuard` caps grid limits and validates unsafe bulk operation input. By default `CrudResource::maxPageSize()` returns `200`:

```php
public function maxPageSize(): int
{
    return 100;
}
```

Bulk actions still require explicit selected IDs unless the action calls `allowRunByFilter()`.

## Debug information

When `ADMIN_KIT_DEBUG` is `true` and the current Bitrix user is an administrator, grid query diagnostics are logged with ORM params, execution time, row count placeholder, count usage, and cache usage. Debug data is not shown to regular users.
