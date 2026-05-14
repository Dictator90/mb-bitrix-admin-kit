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

namespace Bitrix\UI\EntitySelector;

if (!class_exists(BaseProvider::class)) {
    abstract class BaseProvider
    {
        public function __construct(array $options = [])
        {
        }
    }
}
