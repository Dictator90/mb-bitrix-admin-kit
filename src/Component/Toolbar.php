<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use Bitrix\UI\Buttons\AddButton;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Toolbar\Toolbar as BitrixToolbar;

class Toolbar
{
    protected array $leftButtons = [];
    protected array $rightButtons = [];
    protected ?string $title = null;
    protected ?string $filterHtml = null;

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function addButton(string $url, string $text = ''): static
    {
        $this->leftButtons[] = Button::add($url, $text !== '' ? $text : LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_TOOLBAR_ADD', 'Add'));

        return $this;
    }

    public function leftButton(string $html): static
    {
        $this->leftButtons[] = $html;

        return $this;
    }

    public function rightButton(string $html): static
    {
        $this->rightButtons[] = $html;

        return $this;
    }

    public function render(): void
    {
        Extension::load(['ui.buttons']);

        echo '<div class="adm-workarea-toolbar">';

        if ($this->title !== null) {
            echo '<div class="adm-toolbar-title">' . htmlspecialcharsbx($this->title) . '</div>';
        }

        if (!empty($this->leftButtons) || !empty($this->rightButtons)) {
            echo '<div class="adm-toolbar-content">';

            if (!empty($this->leftButtons)) {
                echo '<div class="adm-toolbar-left">';
                foreach ($this->leftButtons as $btn) {
                    echo $btn;
                }
                echo '</div>';
            }

            if (!empty($this->rightButtons)) {
                echo '<div class="adm-toolbar-right">';
                foreach ($this->rightButtons as $btn) {
                    echo $btn;
                }
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Renders Bitrix native Toolbar with Add button using PHP Bitrix\UI\Toolbar.
     */
    public static function renderAddButton(string $url, string $title = ''): void
    {
        Extension::load(['ui.buttons']);
        $title = $title !== '' ? $title : LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_TOOLBAR_ADD', 'Add');

        if (class_exists(BitrixToolbar::class) && class_exists(AddButton::class)) {
            $toolbar = new BitrixToolbar();
            $toolbar->addButton(
                (new AddButton())
                    ->setLink($url)
                    ->setText($title)
                    ->setColor(Color::SUCCESS)
            );
            $toolbar->show();
        } else {
            echo Button::add($url, $title);
        }
    }

}
