# Как использовать import/export

Экспорт в UI доступен (CSV), import UI временно отключен.

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxExportRows(): int { return 5000; }
```

Подробно: [Guide: Import/Export](../guides/import-export.md)
