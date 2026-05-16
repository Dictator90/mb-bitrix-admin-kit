<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone;

use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

/** Base for arbitrary admin pages (dashboards, reports, integration pages). */
abstract class CustomPage extends StandalonePage
{
    /** @var string[] Bitrix UI extensions to load before rendering. */
    protected array $extensions = [];

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->pageType = PageType::CUSTOM;
    }

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
        echo $this->renderToolbarHtml();
        echo '<div class="adminkit-page__content">' . $this->content() . '</div>';
        echo '</div>';
    }

    protected function renderToolbarHtml(): string
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
