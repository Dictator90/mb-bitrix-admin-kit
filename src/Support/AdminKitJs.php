<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

final class AdminKitJs
{
    /**
     * @param array<string,mixed> $config
     */
    public static function renderInit(string $module, array $config): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        $moduleJs = json_encode($module, JSON_UNESCAPED_UNICODE);

        echo '<script>BX.ready(function(){var m=' . $moduleJs . ';if(window.MB&&MB.AdminKit&&MB.AdminKit[m]&&MB.AdminKit[m].init){MB.AdminKit[m].init(' . $json . ');}});</script>';
    }
}
