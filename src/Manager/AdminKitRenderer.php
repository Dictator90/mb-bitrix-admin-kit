<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Pages\AbstractPage;

final class AdminKitRenderer
{
    public function render(ResourcePage|AbstractPage|NotFoundPage $page): string
    {
        ob_start();
        $result = $page->render();
        $buffer = ob_get_clean() ?: '';

        return is_string($result) ? $buffer . $result : $buffer;
    }
}
