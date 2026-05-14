<?php

declare(strict_types=1);

namespace {
spl_autoload_register(function (string $class): void {
    $prefix = 'MB\\Bitrix\\AdminKit\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) { require $file; return; }
    }
    $prefix = 'MB\\Bitrix\\AdminKit\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) { require $file; }
    }
});
}

namespace MB\Support {
    if (!class_exists(Collection::class)) {
        class Collection extends \ArrayObject { public function all(): array { return $this->getArrayCopy(); } }
    }
    if (!class_exists(Str::class)) {
        class Str {
            public static function slug(string $value, string $separator = '-'): string {
                $value = preg_replace('/[^\pL\pN]+/u', $separator, $value) ?: '';
                return trim(mb_strtolower($value), $separator);
            }
        }
    }
}

namespace MB\Support\Conditionable {
    class ConditionTree {
        private array $conditions = [];
        private array $context = [];
        public static function create(array $contexts = []): static { return new static(); }
        public function where(string $field, string $operator, mixed $value): static { $this->conditions[] = [$field, $operator, $value]; return $this; }
        public function context(object|array|string $context, ?string $alias): static { if (is_array($context)) { $this->context = $context; } return $this; }
        public function calculate(): CalculationResult { $conditions = $this->conditions; $context = $this->context; return new CalculationResult(function () use ($conditions, $context) { foreach ($conditions as [$field, $operator, $value]) { $actual = $context[$field] ?? ($context['form'][$field] ?? null); if ((string)$actual !== (string)$value) return false; } return true; }, null); }
    }
    class CalculationResult { public function __construct(private $resolver, private $context = null) {} public function result(): bool { return (bool)($this->resolver)(); } }
}


namespace Bitrix\Main {
    class Application {
        public static ?object $connection = null;
        public static function getConnection(): object { return self::$connection ??= new class {
            public array $calls = [];
            public function startTransaction(): void { $this->calls[] = 'start'; }
            public function commitTransaction(): void { $this->calls[] = 'commit'; }
            public function rollbackTransaction(): void { $this->calls[] = 'rollback'; }
        }; }
    }
    class HttpRequest { public function isPost(): bool { return false; } public function get(string $key): mixed { return null; } public function getPost(string $key): mixed { return null; } }
    class Context { public static function getCurrent(): object { return new class { public function getRequest(): HttpRequest { return new HttpRequest(); } }; } }
}

namespace Bitrix\Main\Grid { class Options { public function __construct(private string $id) {} public function getSorting(array $params): array { return $params; } public function getNavParams(array $params): array { return $params; } } }
namespace Bitrix\Main\UI\Filter { class Options { public static array $filters = []; public function __construct(private string $id) {} public function getFilter(): array { return self::$filters[$this->id] ?? []; } } }

namespace Bitrix\Main\ORM\Fields\Relations { class Reference { public function __construct(public string $name) {} } }
namespace Bitrix\Main\ORM\Fields { class ExpressionField { public function __construct(public string $name) {} } }

namespace Bitrix\Main\UI { class PageNavigation { private int $size = 20; public function __construct(private string $id) {} public function allowAllRecords(bool $v): static { return $this; } public function setPageSize(int $s): static { $this->size=$s; return $this; } public function initFromUri(): static { return $this; } public function getLimit(): int { return $this->size; } public function getOffset(): int { return 0; } public function getPageSize(): int { return $this->size; } public function getCurrentPage(): int { return 1; } public function setRecordCount(int $c): void {} } }

namespace {
    function htmlspecialcharsbx($string, $flags = ENT_QUOTES | ENT_SUBSTITUTE, $encoding = 'UTF-8'): string { return htmlspecialchars((string)$string, $flags, $encoding); }
    function bitrix_sessid(): string { return 'sessid'; }
    function check_bitrix_sessid(): bool { return true; }
}

namespace Bitrix\Main\UI { if (!class_exists(Extension::class)) { class Extension { public static array $loaded = []; public static function load(array|string $extensions): void { self::$loaded[] = (array)$extensions; } } } }
namespace Bitrix\Main\Config { if (!class_exists(Option::class)) { class Option { public static array $values = []; public static function get(string $moduleId, string $name, mixed $default = '', string $siteId = ''): mixed { return self::$values[$moduleId][$siteId][$name] ?? $default; } public static function set(string $moduleId, string $name, string $value, string $siteId = ''): void { self::$values[$moduleId][$siteId][$name] = $value; } public static function delete(string $moduleId, array $filter): void { unset(self::$values[$moduleId][$filter['site_id'] ?? ''][$filter['name'] ?? '']); } } } }
namespace Bitrix\Main { if (!class_exists(SiteTable::class)) { class SiteTable { public static function getList(array $params): object { return new class { public function fetchAll(): array { return []; } }; } } } }
namespace { if (!function_exists('bitrix_sessid_post')) { function bitrix_sessid_post(): string { return '<input type="hidden" name="sessid" value="sessid">'; } } if (!function_exists('LocalRedirect')) { function LocalRedirect(string $url): void { $GLOBALS['last_redirect'] = $url; } } $GLOBALS['APPLICATION'] = new class { public string $title = ''; public array $css = []; public array $js = []; public function SetTitle(string $title): void { $this->title = $title; } public function SetAdditionalCSS(string $path): void { $this->css[] = $path; } public function AddHeadScript(string $path): void { $this->js[] = $path; } }; }
