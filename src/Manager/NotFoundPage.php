<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Support\LocalizedMessage;

final class NotFoundPage
{
    public function render(): void
    {
        global $APPLICATION;

        $APPLICATION->IncludeComponent('bitrix:ui.info.error', '', [
            'TITLE' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_NOT_FOUND_TITLE', 'Page not found'),
            'DESCRIPTION' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_NOT_FOUND_DESCRIPTION', 'The requested resource is not registered in AdminKit.'),
        ]);
    }
}
