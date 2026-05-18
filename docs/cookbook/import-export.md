# Как сделать CSV export

> **Import UI временно отключён** на index-страницах. Ниже — только export; про library import см. `docs/import.md`.

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxExportRows(): int { return 5000; }
```

Экспорт требует выбранные ID или разрешённый фильтр и проверяет `canView()`. Полный export all должен оставаться выключенным, пока Resource не разрешит его явно. Pre-flight count не даст выгрузить больше `maxExportRows()` строк.
