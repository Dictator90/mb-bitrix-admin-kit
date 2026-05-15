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



namespace Bitrix\Main\ORM\Data;

if (!class_exists(DataManager::class)) {
    abstract class DataManager
    {
    }
}

namespace Bitrix\Main\Grid;

if (!class_exists(Options::class)) {
    class Options
    {
        public function __construct(string $id)
        {
        }

        public function getSorting(array $params): array
        {
            return $params;
        }

        public function getNavParams(array $params): array
        {
            return $params;
        }
    }
}

namespace Bitrix\Main\Grid\Panel;

if (!class_exists(Snippet::class)) {
    class Snippet
    {
        public function getEditButton(): array
        {
            return [];
        }
    }
}


namespace Bitrix\Main\UI;

if (!class_exists(PageNavigation::class)) {
    class PageNavigation
    {
        public function __construct(string $id)
        {
        }

        public function allowAllRecords(bool $value): self
        {
            return $this;
        }

        public function setPageSize(int $size): self
        {
            return $this;
        }

        public function initFromUri(): self
        {
            return $this;
        }

        public function setRecordCount(int $count): void
        {
        }

        public function getPageSize(): int
        {
            return 20;
        }

        public function getCurrentPage(): int
        {
            return 1;
        }

        public function getOffset(): int
        {
            return 0;
        }

        public function getLimit(): int
        {
            return 20;
        }
    }
}

namespace Bitrix\UI\Buttons;

if (!class_exists(Button::class)) {
    class Button
    {
        public function __construct(array $params)
        {
        }
    }

    final class Color
    {
        public const SUCCESS = 'success';
    }

    final class Icon
    {
        public const ADD = 'add';
    }

    class JsCode
    {
        public function __construct(string $code)
        {
        }
    }
}

namespace Bitrix\UI\Toolbar;

if (!class_exists(ButtonLocation::class)) {
    final class ButtonLocation
    {
        public const AFTER_TITLE = 'after_title';
    }
}

namespace Bitrix\UI\Toolbar\Facade;

if (!class_exists(Toolbar::class)) {
    final class Toolbar
    {
        public static function addFilter(array $params): void
        {
        }

        public static function addButton(object $button, string $location): void
        {
        }
    }
}