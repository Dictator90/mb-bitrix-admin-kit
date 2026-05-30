<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\UI\Toolbar\ButtonLocation;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Support\Conditionable\ConditionTree;

/**
 * Кнопка тулбара. Может быть:
 *  - простой кнопкой/ссылкой (url или onclick);
 *  - кнопкой с выпадающим меню из вложенных действий (items);
 *  - split-кнопкой (основное действие + меню), если включить split().
 *
 * @see \MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer как это превращается в Bitrix\UI\Buttons\Button.
 */
final class ToolbarAction
{
    private mixed $visibility = null;
    private ?string $color = null;
    private ?string $icon = null;
    private ?string $onclick = null;
    private bool $split = false;
    private string $location = ButtonLocation::AFTER_TITLE;
    private int|string|null $counter = null;
    private ?string $size = null;
    private bool $disabled = false;
    private bool $round = false;
    private ?string $collapsedIcon = null;

    /** @var array{width?:int,gridId?:string|null}|null */
    private ?array $sidePanel = null;

    /** @var list<self> */
    private array $items = [];

    public function __construct(
        private string $id,
        private string $label,
        private string $url = '#',
        private string $class = 'ui-btn ui-btn-light-border'
    ) {
    }

    public static function make(string $label, string $id = ''): self
    {
        return new self($id !== '' ? $id : AdminString::id('toolbar', $label), $label);
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function class(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /** Цвет кнопки — значение Bitrix\UI\Buttons\Color (например 'success'). */
    public function color(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /** Иконка кнопки — значение Bitrix\UI\Buttons\Icon (например 'add'). */
    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /** Произвольный JS-обработчик клика вместо перехода по url. */
    public function onclick(?string $js): self
    {
        $this->onclick = $js;

        return $this;
    }

    /** Счётчик-бейдж на кнопке. */
    public function counter(int|string|null $counter): self
    {
        $this->counter = $counter;

        return $this;
    }

    /** Размер кнопки — значение Bitrix\UI\Buttons\Size (например 'ui-btn-sm'). */
    public function size(?string $size): self
    {
        $this->size = $size;

        return $this;
    }

    /** Отключённое состояние кнопки (Bitrix\UI\Buttons\State::DISABLED). */
    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /** Круглая кнопка. */
    public function round(bool $round = true): self
    {
        $this->round = $round;

        return $this;
    }

    /** Иконка для адаптивного «свёрнутого» состояния (Bitrix\UI\Buttons\Icon). */
    public function collapsedIcon(?string $icon): self
    {
        $this->collapsedIcon = $icon;

        return $this;
    }

    /**
     * Открывать url в слайдере (side-panel) вместо полной перезагрузки.
     * gridId по умолчанию — текущий грид (перезагрузится при закрытии слайдера).
     */
    public function sidePanel(int $width = 1100, ?string $gridId = null): self
    {
        $this->sidePanel = ['width' => $width, 'gridId' => $gridId];

        return $this;
    }

    /** @return array{width?:int,gridId?:string|null}|null */
    public function getSidePanel(): ?array
    {
        return $this->sidePanel;
    }

    /** Рендерить как split-кнопку (основное действие + стрелка с меню из items). */
    public function split(bool $split = true): self
    {
        $this->split = $split;

        return $this;
    }

    /**
     * Позиция кнопки в тулбаре. Значения Bitrix\UI\Toolbar\ButtonLocation:
     * AFTER_TITLE (по умолчанию), RIGHT, AFTER_FILTER, AFTER_NAVIGATION.
     */
    public function location(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Вложенные пункты (ссылки/действия). Их наличие превращает кнопку в дропдаун.
     *
     * @param iterable<self> $items
     */
    public function items(iterable $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    public function addItem(self $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function canSee(bool|callable|ConditionTree $condition): self
    {
        $this->visibility = $condition;

        return $this;
    }

    /** @param array<string,mixed> $context */
    public function isVisible(array $context = []): bool
    {
        return AdminCondition::evaluate($this->visibility, $context);
    }

    public function render(): string
    {
        return '<a id="' . htmlspecialcharsbx($this->id) . '" class="' . htmlspecialcharsbx($this->class) . '" href="' . htmlspecialcharsbx($this->url) . '">' . htmlspecialcharsbx($this->label) . '</a>';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getOnclick(): ?string
    {
        return $this->onclick;
    }

    public function isSplit(): bool
    {
        return $this->split;
    }

    public function getCounter(): int|string|null
    {
        return $this->counter;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function isRound(): bool
    {
        return $this->round;
    }

    public function getCollapsedIcon(): ?string
    {
        return $this->collapsedIcon;
    }

    public function hasMenu(): bool
    {
        return $this->items !== [];
    }

    /** @return list<self> */
    public function getItems(): array
    {
        return $this->items;
    }
}
