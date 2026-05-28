# Select

Класс: `MB\Bitrix\AdminKit\Field\Select`.

Назначение: выбор из списка.

## Доступные методы

- `options(array|Closure|OptionsResolverContract $options)` — задает источник опций (массив, callback или resolver-объект).
- `cache(int $ttl)` — включает кэширование результата resolver-а опций на `ttl` секунд.
- `multiple(bool $multiple = true)` — включает множественный выбор.

## Примеры options()

### Массив

```php
use MB\Bitrix\AdminKit\Field\Select;

Select::make('Тип', 'TYPE')
    ->options([
        'simple' => 'Simple',
        'service' => 'Service',
    ])
    ->default('simple');
```

### Closure

```php
use MB\Bitrix\AdminKit\Field\Select;

Select::make('Город', 'CITY_ID')
    ->options(static function (array $context, Select $field): array {
        // $context приходит из текущего окружения (например, данные формы/запроса)
        $countryId = (int)($context['COUNTRY_ID'] ?? 0);

        if ($countryId <= 0) {
            return [];
        }

        // Пример: динамический набор опций в зависимости от контекста
        return [
            1 => 'Москва',
            2 => 'Санкт-Петербург',
            3 => 'Казань',
        ];
    })
    ->cache(300);
```

### OptionsResolverContract

```php
use MB\Bitrix\AdminKit\Field\Options\ArrayOptionsResolver;
use MB\Bitrix\AdminKit\Field\Select;

Select::make('Тип', 'TYPE')
    ->options(new ArrayOptionsResolver([
        'simple' => 'Simple',
        'service' => 'Service',
    ]));
```

### 4) Кастомный резолвер (`OptionsResolverContract`)

```php
use MB\Bitrix\AdminKit\Field\Options\OptionsResolverContract;
use MB\Bitrix\AdminKit\Field\Select;

final class ActiveStatusesResolver implements OptionsResolverContract
{
    public function resolve(array $context, Select $field): array
    {
        unset($context, $field);

        return [
            'new' => 'Новый',
            'work' => 'В работе',
            'done' => 'Завершен',
        ];
    }
}

Select::make('Статус', 'STATUS')
    ->options(new ActiveStatusesResolver());
```

## Значения по умолчанию

- `options = []` (пустой набор опций).
- `cacheTtl = 0` (кэширование опций отключено).
- `multiple = false` (унаследовано из базового `Field`).
