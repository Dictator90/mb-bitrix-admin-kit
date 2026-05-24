# OptionsPage

`OptionsPage` — standalone-страница для хранения/редактирования настроек.

Минимальный API:
- `public static function getId(): string`
- `public static function getTitle(): string`
- `public function fields(): iterable`
- при модульном сценарии обычно `protected string $moduleId = '<module_id>'`

## Как запустить

- В модуле: [module integration](user/guides/module-integration.md)
- Вне модуля: [standalone integration](user/guides/standalone-integration.md)
- Пошаговый рецепт: [cookbook/options-page](user/cookbook/options-page.md)

## Важно про module id/storage

Для module scope опции пишутся в module id.
Для standalone scope опции сохраняются через `main`, поэтому используйте стабильный `scopeId`.
