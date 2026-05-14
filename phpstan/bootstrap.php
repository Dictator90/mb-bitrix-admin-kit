<?php

declare(strict_types=1);

namespace MB\Bitrix\Foundation;

if (!class_exists(ServiceProvider::class)) {
    abstract class ServiceProvider
    {
        public function register(): void
        {
        }

        public function boot(): void
        {
        }
    }
}
