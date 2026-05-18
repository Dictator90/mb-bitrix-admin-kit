<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Page\StandalonePage;

final class AdminKitRenderer
{
    public function render(ResourcePage|StandalonePage|NotFoundPage $page): string
    {
        ob_start();
        $result = $page->render();
        $buffer = ob_get_clean() ?: '';

        return is_string($result) ? $buffer . $result : $buffer;
    }
}
