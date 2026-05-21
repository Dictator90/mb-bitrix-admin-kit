# Тестирование в MB Bitrix AdminKit

Пакет `mb4it/bitrix-admin-kit` имеет двухуровневую тестовую структуру: **Unit-тесты** и **Интеграционные тесты**.

---

## 1. Unit-тесты

Unit-тесты проверяют независимую бизнес-логику пакета (поля, фильтры, контексты страниц, конфигурации) без реального подключения к базе данных или ядру 1С-Битрикс. Вместо этого они используют легковесные заглушки (mocks), определённые в файле `tests/bootstrap.php`.

### Запуск Unit-тестов:
```bash
composer test:unit
```

---

## 2. Интеграционные тесты

Интеграционные тесты проверяют реальное взаимодействие с базой данных SQLite и компонентами 1С-Битрикс через вспомогательный пакет `mb4it/bitrix-test-core`. 

В этих тестах инициализируется настоящее ядро Битрикс, включая:
* Поддержку `Bitrix\Main\Config\Option` для сохранения и удаления настроек.
* Поддержку Bitrix D7 ORM (`DataManager` и `EntityObject`) для создания, изменения и удаления записей.
* Поддержку транзакций и работы со связями `BelongsToMany` через промежуточные таблицы.
* Корректную инициализацию HTTP-запроса, ответа и сервера в `Bitrix\Main\Context`.

### Запуск Интеграционных тестов:
```bash
composer test:integration
```

---

## 3. Запуск всего тестового набора

Для запуска всех тестов последовательно (сначала Unit, затем Integration):
```bash
composer test
```

---

## 4. Конфигурация интеграционного окружения

Интеграционная среда конфигурируется в файле `tests/bootstrap-integration.php`:
1. Запуск виртуального окружения ядра Битрикс с корнем в директории `tests/.runtime/integration`.
2. Создание и пересоздание встраиваемой базы данных SQLite по пути `tests/.runtime/integration/sqlite/bitrix.sqlite`.
3. Создание служебных таблиц Битрикс `b_option` и `b_option_site` для корректной работы сохранения настроек.
4. Инициализация глобального `Bitrix\Main\Context` с фейковыми HTTP-запросом, HTTP-ответом и сервером для поддержки вызовов `Context::getCurrent()->getRequest()`.

При создании новых интеграционных тестов вы можете добавлять создание ваших тестовых таблиц в методе `setUpBeforeClass` вашего тестового класса.

Пример:
```php
use Bitrix\Main\Application;

public static function setUpBeforeClass(): void
{
    parent::setUpBeforeClass();
    $connection = Application::getConnection();
    $connection->queryExecute("
        CREATE TABLE IF NOT EXISTS b_my_table (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            NAME VARCHAR(255) NOT NULL
        )
    ");
}
```
