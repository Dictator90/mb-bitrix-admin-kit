# Как сделать import/export

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxImportRows(): int { return 1000; }
```

CSV import использует `Form\DataPipeline`, поэтому Field `normalize()` и validation совпадают с обычной формой. Полный export должен оставаться выключенным, пока Resource не разрешит его явно.
