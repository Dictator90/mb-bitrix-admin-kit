# File

Класс: `MB\Bitrix\AdminKit\Field\File`.

Назначение: загрузка файла с сохранением file ID.

## Доступные методы

- `allowedExtensions(array $extensions)` — ограничивает расширения загружаемых файлов.
- `maxFileSize(int $bytes)` — задает максимальный размер файла.
- `uploadDir(string $dir)` — задает директорию загрузки в файловом хранилище.

Пример:
```php
File::make('Документ', 'DOC_FILE_ID')
    ->allowedExtensions(['pdf', 'docx'])
    ->maxFileSize(5 * 1024 * 1024);
```

## Значения по умолчанию

- `allowedExtensions = []` (без ограничения по расширениям).
- `maxFileSize = null` (без ограничения размера).
- `uploadDir = "adminkit"`.
