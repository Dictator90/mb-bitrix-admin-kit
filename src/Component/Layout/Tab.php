<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;

class Tab
{
    protected ?string $id = null;
    protected string $title;
    /** @var array<int, FieldContract|ComponentContract> */
    protected array $items = [];
    protected bool $active = false;
    protected ?string $description = null;
    /** Icon-set class for the tab header (e.g. '--settings'). Rendered via mb.admin.kit Tabs. */
    protected ?string $icon = null;
    /** Badge counter on the tab header (e.g. 3 or '99+'). Rendered via mb.admin.kit Tabs. */
    protected int|string|null $count = null;

    /**
     * @param string $title Human-readable tab label.
     * @param array<int, FieldContract|ComponentContract> $items Optional initial items.
     */
    public function __construct(string $title, array $items = [])
    {
        $this->title = $title;
        $this->items = $items;
    }

    /**
     * @param string $title Human-readable tab label.
     * @param array<int, FieldContract|ComponentContract> $items Optional initial items.
     */
    public static function make(string $title, array $items = []): static
    {
        return new static($title, $items);
    }

    /**
     * Explicit DOM/routing ID.
     * If omitted, the ID is auto-derived from the title (ASCII slug → hash fallback).
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    // ── Items ────────────────────────────────────────────────────────────

    /** Append items (variadic). */
    public function fields(FieldContract|ComponentContract ...$items): static
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /** Append a single item. */
    public function field(FieldContract|ComponentContract $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /** Append items (array form, useful for spread). */
    public function with(array $items): static
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    // ── Head extras ─────────────────────────────────────────────────────

    /**
     * Tooltip/subtitle shown on the tab header (mb.admin.kit Tabs).
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Icon-set CSS class shown before the title (e.g. '--settings', '--lock').
     * Rendered via mb.admin.kit Tabs.
     */
    public function icon(string $iconClass): static
    {
        $this->icon = $iconClass;

        return $this;
    }

    /**
     * Counter badge on the tab header (e.g. 3 or '99+').
     * Rendered via mb.admin.kit Tabs.
     */
    public function count(int|string $count): static
    {
        $this->count = $count;

        return $this;
    }

    // ── Active ───────────────────────────────────────────────────────────

    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    // ── Getters ──────────────────────────────────────────────────────────

    /**
     * Returns the explicit ID or auto-generates one from the title.
     * Auto-ID rules:
     *  - ASCII title  → lowercase slug   ('My Settings' → 'my_settings')
     *  - Non-ASCII    → 'tab_' + md5[:8] ('Логирование' → 'tab_3a9f...')
     *  - Empty title  → 'tab_' + random hash
     */
    public function getId(): string
    {
        if ($this->id !== null) {
            return $this->id;
        }

        if ($this->title !== '') {
            $slug = preg_replace('/[^a-zA-Z0-9]+/', '_', $this->title) ?? '';
            $slug = strtolower(trim($slug, '_'));

            return $slug !== '' ? $slug : 'tab_' . substr(md5($this->title), 0, 8);
        }

        return 'tab_' . substr(md5((string)mt_rand()), 0, 8);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getCount(): int|string|null
    {
        return $this->count;
    }

    /**
     * Build the `head` option array for the MB.AdminKit.Tabs JS constructor.
     */
    public function getHeadOptions(): array
    {
        $head = ['title' => $this->title];

        if ($this->description !== null) {
            $head['description'] = $this->description;
        }

        if ($this->icon !== null) {
            $head['icon'] = $this->icon;
        }

        if ($this->count !== null) {
            $head['count'] = $this->count;
        }

        return $head;
    }

    /** @return array<int, FieldContract|ComponentContract> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
