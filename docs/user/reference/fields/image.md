# Image

Класс: `MB\Bitrix\AdminKit\Field\Image`.

Наследник поля [File](file.md)

Назначение: загрузка изображения с превью.

## Доступные методы

- `previewSize(int $width, int $height)` — задает размер превью изображения в форме и в гриде.

Пример:
```php
Image::make('Фото', 'PHOTO_ID')->previewSize(120, 120);
```

## Значения по умолчанию

- `previewWidth = 80`
- `previewHeight = 80`
