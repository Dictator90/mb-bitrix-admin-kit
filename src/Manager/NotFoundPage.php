<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

final class NotFoundPage
{
    public function render(): void
    {
        global $APPLICATION;

        $APPLICATION->IncludeComponent('bitrix:ui.info.error', '', [
            'TITLE'       => 'Страница не найдена',
            'DESCRIPTION' => 'Запрашиваемый ресурс не зарегистрирован в AdminKit.',
        ]);
    }
}
