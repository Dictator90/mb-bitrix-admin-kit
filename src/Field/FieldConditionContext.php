<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

final readonly class FieldConditionContext
{
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(
        public array $data = [],
        public ?PageType $pageType = null,
        public ?string $mode = null,
        public mixed $item = null,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data, ?PageType $pageType = null, mixed $item = null): self
    {
        $mode = null;
        if (isset($data['_mode']) && is_scalar($data['_mode'])) {
            $mode = (string) $data['_mode'];
        }

        return new self($data, $pageType, $mode, $item);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
    public function is(string $key, mixed $value): bool
    {
        return (string) ($this->data[$key] ?? '') === (string) $value;
    }
    public function isNot(string $key, mixed $value): bool
    {
        return !$this->is($key, $value);
    }
    /** @param list<mixed> $values */
    public function in(string $key, array $values): bool
    {
        return in_array($this->data[$key] ?? null, $values, true);
    }
    public function isCreate(): bool
    {
        return $this->mode === 'create';
    }
    public function isEdit(): bool
    {
        return $this->mode === 'edit' || ($this->data['_id'] ?? '') !== '' || ($this->data['ID'] ?? '') !== '';
    }
}
