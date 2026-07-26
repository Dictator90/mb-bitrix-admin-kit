<?php

declare(strict_types=1);




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

namespace Bitrix\Main\ORM\Entity;

if (!class_exists(Entity::class)) {
    class Entity
    {
        public function hasField(string $fieldName): bool
        {
            return false;
        }

        public function getField(string $fieldName): object
        {
            return new class {};
        }

        public function getName(): string
        {
            return '';
        }
    }
}

namespace Bitrix\Main\ORM\Objectify;

if (!class_exists(EntityObject::class)) {
    class EntityObject
    {
        public function set(string $fieldName, mixed $value): self
        {
            return $this;
        }

        public function get(string $fieldName): mixed
        {
            return null;
        }

        public function getId(): mixed
        {
            return null;
        }

        public function getEntity(): \Bitrix\Main\ORM\Entity\Entity
        {
            return new \Bitrix\Main\ORM\Entity\Entity();
        }

        public function save(): object
        {
            return new class {
                public function isSuccess(): bool
                {
                    return true;
                }

                /** @return list<string> */
                public function getErrorMessages(): array
                {
                    return [];
                }

                public function getId(): mixed
                {
                    return null;
                }
            };
        }
    }
}

namespace Bitrix\Main\ORM\Query;

if (!class_exists(Join::class)) {
    class Join
    {
        /** @return array<string, string> */
        public static function on(string $left, string $right): array
        {
            return [];
        }
    }
}

namespace Bitrix\Main\ORM\Fields\Relations;

if (!class_exists(Reference::class)) {
    class Reference
    {
        /**
         * @param array<string, string> $referenceFilter
         * @param array<string, mixed> $parameters
         */
        public function __construct(string $name, string $referenceEntity, array $referenceFilter, array $parameters = [])
        {
        }
    }
}

if (!class_exists(OneToMany::class)) {
    class OneToMany
    {
        public function __construct(string $name, string $relatedEntity, string $foreignKey)
        {
        }
    }
}

if (!class_exists(ManyToMany::class)) {
    class ManyToMany
    {
        public function __construct(string $name, string $relatedEntity)
        {
        }

        public function configureMediatorEntity(string $entity): self
        {
            return $this;
        }

        public function configureLocalPrimary(string $fieldName, string $mediatorFieldName = ''): self
        {
            return $this;
        }

        public function configureRemotePrimary(string $fieldName, string $mediatorFieldName = ''): self
        {
            return $this;
        }

        public function configureLocalReference(string $key): self
        {
            return $this;
        }

        public function configureRemoteReference(string $key): self
        {
            return $this;
        }
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

        public function setCurrentPage(int $page): self
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

        public function allRecordsShown(): bool
        {
            return false;
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

        /** @param array<string, mixed> $menu */
        public function setMenu(array $menu): self
        {
            return $this;
        }
    }

    final class Color
    {
        public const PRIMARY = 'primary';
        public const SUCCESS = 'success';
        public const SECONDARY = 'secondary';
        public const LIGHT_BORDER = 'light-border';
        public const LINK = 'link';
        public const DANGER = 'danger';
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
        public const AFTER_FILTER = 'after_filter';
        public const AFTER_TITLE = 'after_title';
        public const RIGHT = 'right';
    }
}

namespace Bitrix\UI\Buttons\Split;

if (!class_exists(Button::class)) {
    class Button extends \Bitrix\UI\Buttons\Button
    {
        public function setDisabled(bool $disabled = true): self
        {
            return $this;
        }
    }

    final class Type
    {
        public const MENU = 'menu';
    }
}

namespace Bitrix\UI\Buttons;

if (!class_exists(State::class)) {
    final class State
    {
        public const DISABLED = 'disabled';
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

        public static function setTitle(string $title): void
        {
        }

        public static function addEditableTitle(): void
        {
        }

        public static function addFavoriteStar(): void
        {
        }

        public static function setCopyLinkButton(array $params): void
        {
        }

        public static function addBeforeTitleHtml(string $html): void
        {
        }

        public static function addAfterTitleHtml(string $html): void
        {
        }

        public static function addUnderTitleHtml(string $html): void
        {
        }

        public static function addRightCustomHtml(string $html): void
        {
        }
    }
}

namespace Bitrix\Main\Localization;

if (!class_exists(Loc::class)) {
    final class Loc
    {
        public static function loadMessages(string $file): void
        {
        }

        /** @param array<string,string>|null $replace */
        public static function getMessage(string $code, ?array $replace = null): ?string
        {
            return null;
        }
    }
}

namespace Bitrix\Main;

if (!class_exists(Loader::class)) {
    final class Loader
    {
        public static function includeModule(string $moduleName): bool
        {
            return false;
        }

        public static function getLocal(string $path): ?string
        {
            return null;
        }
    }
}

if (!class_exists(HttpRequest::class)) {
    class HttpRequest
    {
        public function isPost(): bool
        {
            return false;
        }

        public function get(string $key): mixed
        {
            return null;
        }

        public function getPost(string $key): mixed
        {
            return null;
        }

        public function getRequestUri(): string
        {
            return '';
        }

        /** @return array<string,mixed> */
        public function toArray(): array
        {
            return [];
        }

        public function getHeader(string $name): ?string
        {
            return null;
        }
    }
}

namespace Bitrix\Main\Security;

if (!class_exists(Random::class)) {
    final class Random
    {
        public static function getString(int $length, bool $caseSensitive = false): string
        {
            return str_repeat('a', $length);
        }
    }
}

namespace Bitrix\Main\UI;

if (!class_exists(Extension::class)) {
    final class Extension
    {
        /** @param string|list<string> $extensions */
        public static function load(string|array $extensions): void
        {
        }
    }
}

namespace Bitrix\Main;

if (!class_exists(SiteTable::class)) {
    final class SiteTable
    {
        public static function getList(array $params): object
        {
            return new class {
                public function fetchAll(): array
                {
                    return [];
                }
            };
        }
    }
}

namespace Bitrix\Main\Config;

if (!class_exists(Option::class)) {
    final class Option
    {
        public static function get(string $moduleId, string $name, mixed $default = null, string $siteId = ''): mixed
        {
            return $default;
        }

        public static function set(string $moduleId, string $name, mixed $value = '', string $siteId = ''): void
        {
        }

        public static function delete(string $moduleId, array $filter = []): void
        {
        }
    }
}

namespace Bitrix\Main\ORM\Fields;

if (!class_exists(ExpressionField::class)) {
    class ExpressionField
    {
        public function __construct(string $name, string $expression, array $buildFrom = [])
        {
        }

        public function getName(): string
        {
            return '';
        }
    }
}

namespace Bitrix\Highloadblock;

if (!class_exists(DataManager::class)) {
    abstract class DataManager extends \Bitrix\Main\ORM\Data\DataManager
    {
        public static function getEntity(): \Bitrix\Main\ORM\Entity\Entity
        {
            return new \Bitrix\Main\ORM\Entity\Entity();
        }
    }
}

if (!class_exists(HighloadBlockTable::class)) {
    class HighloadBlockTable
    {
        public static function getList(array $params): object
        {
            return new class {
                /** @return array<string, mixed>|false */
                public function fetch()
                {
                    return false;
                }
            };
        }
    }
}
