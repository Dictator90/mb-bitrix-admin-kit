<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Support\AdminCollection;

/** Base for arbitrary admin pages (dashboards, reports, integration pages). */
abstract class CustomPage extends AbstractPage
{
    /** @var string[] Bitrix UI extensions to load before rendering. */
    protected array $extensions = [];

    /** @return iterable<ToolbarAction|string> */
    protected function toolbarActions(): iterable
    {
        return [];
    }

    protected function pageTitle(): string
    {
        return $this->title();
    }

    abstract protected function content(): string;

    public function render(): void
    {
        global $APPLICATION;

        (new AssetManager())->addExtensions($this->extensions)->load();

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'SetTitle')) {
            $APPLICATION->SetTitle($this->title());
        }

        echo '<div class="adminkit-page adminkit-page--custom">';
        echo '<h1 class="adminkit-page__title">' . htmlspecialcharsbx($this->pageTitle()) . '</h1>';
        echo $this->renderToolbar();
        echo '<div class="adminkit-page__content">' . $this->content() . '</div>';
        echo '</div>';
    }

    protected function renderToolbar(): string
    {
        $items = [];
        foreach (AdminCollection::make($this->toolbarActions())->all() as $action) {
            if ($action instanceof ToolbarAction) {
                if ($action->isVisible(['page' => $this])) {
                    $items[] = $action->render();
                }
                continue;
            }

            $items[] = (string)$action;
        }

        if ($items === []) {
            return '';
        }

        return '<div class="adminkit-toolbar">' . implode('', $items) . '</div>';
    }
}
