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

namespace MB\Bitrix\Contracts\Module;

if (!interface_exists(Entity::class)) {
    interface Entity
    {
        public function getLibPath(): string;
    }
}
