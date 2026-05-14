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
        public static function create(array $contexts = []): static { return new static(); }
        public function context(object|array|string $context, ?string $alias): static { return $this; }
        public function calculate(): CalculationResult { return new CalculationResult(fn() => true, null); }
    }
    class CalculationResult { public function __construct(private $resolver, private $context = null) {} public function result(): bool { return (bool)($this->resolver)(); } }
}

namespace Bitrix\Main\Grid { class Options { public function __construct(private string $id) {} public function getSorting(array $params): array { return $params; } public function getNavParams(array $params): array { return $params; } } }
namespace Bitrix\Main\UI\Filter { class Options { public static array $filters = []; public function __construct(private string $id) {} public function getFilter(): array { return self::$filters[$this->id] ?? []; } } }

namespace Bitrix\Main\ORM\Fields\Relations { class Reference { public function __construct(public string $name) {} } }
namespace Bitrix\Main\ORM\Fields { class ExpressionField { public function __construct(public string $name) {} } }

namespace Bitrix\Main\UI { class PageNavigation { public function __construct(private string $id) {} public function allowAllRecords(bool $v): static { return $this; } public function setPageSize(int $s): static { $this->size=$s; return $this; } public function initFromUri(): static { return $this; } public function getLimit(): int { return $this->size ?? 20; } public function getOffset(): int { return 0; } public function getPageSize(): int { return $this->size ?? 20; } public function getCurrentPage(): int { return 1; } public function setRecordCount(int $c): void {} } }

namespace {
    function htmlspecialcharsbx($string, $flags = ENT_QUOTES | ENT_SUBSTITUTE, $encoding = 'UTF-8'): string { return htmlspecialchars((string)$string, $flags, $encoding); }
    function bitrix_sessid(): string { return 'sessid'; }
    function check_bitrix_sessid(): bool { return true; }
}
