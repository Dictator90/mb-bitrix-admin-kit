# AGENTS.md

## Project conventions
- Follow PSR-12 for PHP code.
- Keep the public API backward compatible unless the task explicitly asks for a breaking change.
- Prefer extending existing resource, field, grid, and filter classes before adding new abstractions.
- Use `MB\Bitrix\AdminKit\Support\AdminCollection` instead of global collection helpers.
- Use `MB\Bitrix\AdminKit\Support\AdminString` instead of global string helpers for generated ids, aliases, keys, and HTML ids.
- For Bitrix D7 ORM features, rely on documented Bitrix ORM behavior and pass runtime field objects through to `DataManager::getList()` without embedding business-specific ORM joins in the grid layer.
- Record every user-visible change in `CHANGELOG.md`.

## Frontend conventions
- Follow BEM for markup/CSS work.
- Do not add inline `style` attributes unless there is no practical alternative.

## Testing
- Run the relevant PHPUnit tests after code changes when possible.
