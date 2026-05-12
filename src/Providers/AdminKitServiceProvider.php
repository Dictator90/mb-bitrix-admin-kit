<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Providers;

use MB\Bitrix\Foundation\ServiceProvider;

class AdminKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerComponentPath();
    }

    public function boot(): void
    {
    }

    protected function registerComponentPath(): void
    {
        $componentDir = dirname(__DIR__, 3) . '/install/components';

        if (is_dir($componentDir)) {
            global $arCustomComponentDirs;
            if (!is_array($arCustomComponentDirs)) {
                $arCustomComponentDirs = [];
            }
            if (!in_array($componentDir, $arCustomComponentDirs, true)) {
                $arCustomComponentDirs[] = $componentDir;
            }
        }
    }
}
