# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — библиотека для декларативной админки 1С-Битрикс (CRUD-ресурсы, standalone-страницы, поля, фильтры, действия, импорт/экспорт CSV) на D7 ORM.

## Быстрый старт (2 сценария)

- Внутри Bitrix-модуля: [Полный гайд для модуля](docs/user/getting-started/module-full-guide.md)
- Вне модуля: [Полный гайд для standalone](docs/user/getting-started/standalone-full-guide.md)
- Краткая версия: [Установка](docs/user/getting-started/installation.md) и [Bootstrap](docs/user/getting-started/bootstrap.md)

Дальше:
- Первый ORM CRUD-ресурс: [First CRUD](docs/user/getting-started/first-crud-resource.md)
- Первая standalone-страница: [First Standalone Page](docs/user/getting-started/first-standalone-page.md)

## Карта документации

- Пользовательская документация: [docs/user/README.md](docs/user/README.md)
- Cookbook (короткие рецепты): [docs/user/cookbook/README.md](docs/user/cookbook/README.md)
- Документация для контрибьюторов: [docs/dev/README.md](docs/dev/README.md)

## Ограничения текущей версии

- Import UI на `IndexPage` временно отключен; `Import\*` доступен как библиотечный слой.
- Экспорт — CSV-first (`CsvExporter`), XLSX/Excel не входит в текущий scope.
- Для ORM-ресурсов (`DataManagerResource`) сохранение формы идет через Bitrix `EntityObject`.
- Полный экспорт выключен по умолчанию (`allowExportAll(): false`).
