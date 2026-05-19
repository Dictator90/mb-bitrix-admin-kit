# Инструменты производительности запросов

В v0.6.0 добавлены небольшие opt-in механизмы производительности для гридов ресурсов и lookup полей.

## Отключение total count

Тяжёлые запросы `getCount()` можно отключить на ресурсе:

```php
public function useTotalCount(GridContext $context): bool
{
    return false;
}
```

При отключении запрос данных грида всё равно выполняется, но точный total count не запрашивается.

## Кэш count

Запросы count можно кэшировать по ключу grid/filter/user:

```php
public function countCacheTtl(GridContext $context): int
{
    return 300;
}
```

Ключи кэша генерируются через `AdminString::cacheKey()` из id модуля, id ресурса, id грида, данных хэша фильтра и id текущего пользователя.

## Кэш опций Select

`Select` поддерживает кэшированные провайдеры опций:

```php
Select::make('Статус', 'STATUS')
    ->options(fn () => ['Y' => 'Active', 'N' => 'Inactive'])
    ->cache(3600);
```

Поддерживаются статические массивы и callable-провайдеры.

## Кэш lookup

`RelationResolver` держит кэш на уровне запроса и может использовать TTL-кэш:

```php
$resolver = (new RelationResolver())->cache(3600);
$resolver->preload(ProductTable::class, [1, 2, 3], 'ID', ['ID', 'NAME']);
```

Используйте `preload()` для батчевой подгрузки подписей связей и избежания N+1.

## Query guard и max page size

`QueryGuard` ограничивает limit грида и проверяет небезопасный ввод массовых операций. По умолчанию `CrudResource::maxPageSize()` возвращает `200`:

```php
public function maxPageSize(): int
{
    return 100;
}
```

Массовые действия по-прежнему требуют явно выбранные ID, пока действие не вызовет `allowRunByFilter()`.

## Отладочная информация

Когда `ADMIN_KIT_DEBUG` равен `true` и текущий пользователь Bitrix — администратор, диагностика запросов грида логируется с ORM params, временем выполнения, placeholder числа строк, использованием count и кэша. Обычным пользователям отладочные данные не показываются.
