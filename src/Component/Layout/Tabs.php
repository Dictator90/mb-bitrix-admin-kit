<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Concerns\HasConditionalVisibility;
use MB\Bitrix\AdminKit\Component\Concerns\HasHtmlAttributes;
use MB\Bitrix\AdminKit\Component\Layout\Renderers\TabsRenderer;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;
use MB\Bitrix\AdminKit\Contracts\UI\LayoutComponentContract;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

/**
 * Tabs container — wraps Tab instances and renders using the mb.ui.tabs JS extension.
 *
 * Tab cannot render standalone; it must be inside Tabs.
 *
 * Usage:
 *   Tabs::make([
 *       Tab::make('Основное', [
 *           Text::make('API Key', 'api_key'),
 *       ])->active(),
 *
 *       Tab::make('Дополнительно', [
 *           Switcher::make('Debug', 'debug'),
 *       ])->id('advanced'),
 *   ])
 */
class Tabs implements LayoutComponentContract
{
    use HasHtmlAttributes;
    use HasConditionalVisibility;

    /** @var Tab[] */
    protected array $tabs = [];
    protected ?DataWrapper $item = null;
    protected PageType $pageType = PageType::FORM;
    /**
     * Bitrix JS extension to load. Default is the standard 'ui.tabs' (always available).
     * Switch to 'mb.ui.tabs' (from mb.core) to unlock icon + count badge support on headers.
     */
    protected string $extension = 'mb.ui.tabs';

    protected bool $remember = false;

    protected ?string $rememberStorageKey = null;

    protected ?string $rememberedActiveTabId = null;

    /** @param Tab[] $tabs */
    public function __construct(array $tabs = [])
    {
        $this->tabs = array_values(array_filter($tabs, fn ($t) => $t instanceof Tab));
    }

    /** @param Tab[] $tabs */
    public static function make(array $tabs = []): static
    {
        return new static($tabs);
    }

    public function withItem(?DataWrapper $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function withPageType(PageType $type): static
    {
        $this->pageType = $type;

        return $this;
    }

    /** Override the JS extension name (default: 'ui.tabs'). Use 'mb.ui.tabs' for icon/count support. */
    public function extension(string $name): static
    {
        $this->extension = $name;

        return $this;
    }

    /**
     * Remember the last opened tab in $_SESSION (via OptionsPage) and restore it on next visit.
     */
    public function remember(bool $remember = true, ?string $storageKey = null): static
    {
        $this->remember = $remember;
        $this->rememberStorageKey = $storageKey;

        return $this;
    }

    public function remembersActiveTab(): bool
    {
        return $this->remember;
    }

    public function withRememberedActiveTab(?string $tabId): static
    {
        $clone = clone $this;
        $clone->rememberedActiveTabId = $tabId;

        if ($tabId !== null && $tabId !== '') {
            $clone->activateTabById($tabId);
        }

        return $clone;
    }

    public function render(): string
    {
        if (empty($this->tabs)) {
            return '';
        }

        if ($this->rememberedActiveTabId !== null && $this->rememberedActiveTabId !== '') {
            $this->activateTabById($this->rememberedActiveTabId);
        }

        // Ensure at least one tab is active
        $hasActive = (bool)array_filter($this->tabs, fn (Tab $t) => $t->isActive());
        if (!$hasActive && $this->tabs !== []) {
            $this->tabs[0]->active();
        }

        $containerId = 'adminkit-tabs-' . bin2hex(random_bytes(5));

        $html = (new TabsRenderer())->render(
            tabs: $this->tabs,
            config: new TabsConfig($containerId, $this->extension, $this->remember),
            context: new ComponentContext($this->item, $this->pageType),
        );

        if ($this->classes === [] && $this->styles === [] && $this->attrs === []) {
            return $html;
        }

        return '<div' . $this->buildClassAttr() . $this->buildStyleAttr() . $this->buildExtraAttrs() . '>' . $html . '</div>';
    }

    /** @return list<FieldContract> */
    public function extractFields(): array
    {
        $fields = [];

        foreach ($this->tabs as $tab) {
            foreach ($tab->getItems() as $item) {
                if ($item instanceof FieldContract) {
                    $fields[] = $item;
                } elseif ($item instanceof FieldContainerContract) {
                    $fields = array_merge($fields, $item->extractFields());
                }
            }
        }

        return $fields;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    protected function activateTabById(string $tabId): void
    {
        foreach ($this->tabs as $tab) {
            $tab->active($tab->getId() === $tabId);
        }
    }
}
