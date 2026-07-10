<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Field\Concerns\RepeatableScalar;

/**
 * Поле электронной почты (`input[type=email]`).
 *
 * Одиночное по умолчанию; {@see multiple()} включает повторяемый список адресов
 * с добавлением/удалением (значение — плоский массив строк).
 */
class Email extends Field
{
    use RepeatableScalar;

    protected function scalarInputType(): string
    {
        return 'email';
    }

    protected function defaultAddButtonLabel(): string
    {
        return 'Добавить почту';
    }
}
