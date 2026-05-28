# Upgrade notes (dev)

Текущие важные заметки:
- Import UI на index-страницах отключен.
- ORM-ресурсы должны строиться на `DataManagerResource`.
- Для нестандартного JS в bulk используйте `BulkAction::clientHandler()`.
- Relation-поля используйте только из `Field\Relation\*`.
