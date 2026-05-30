<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Toolbar;

use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\Split\Button as SplitButton;
use Bitrix\UI\Buttons\Split\Type as SplitType;
use Bitrix\UI\Buttons\State;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Manager\SidePanelAdapter;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

final class ToolbarRenderer
{
    public function render(CrudResourceContract $resource, Grid $grid, string $createUrl): void
    {
        global $APPLICATION;
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
        }

        if ($filterParams = $grid->getFilterComponentParams()) {
            Toolbar::addFilter($filterParams);
        }

        $this->applyToolbarFeatures($resource);

        $showCreateButton = !method_exists($resource, 'showCreateButton') || $resource->showCreateButton();

        if ($showCreateButton && $resource->canCreate(new PermissionContext(resource: $resource, operation: 'create'))) {
            $createLabel = LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_CREATE', 'Create');
            if (method_exists($resource, 'createButtonLabel') && ($customLabel = $resource->createButtonLabel()) !== null) {
                $createLabel = $customLabel;
            }

            Toolbar::addButton(
                new Button([
                    'color' => Color::SUCCESS,
                    'icon' => Icon::ADD,
                    'text' => $createLabel,
                    'click' => new JsCode($this->createButtonJs($resource, $grid, $createUrl)),
                ]),
                ButtonLocation::AFTER_FILTER,
            );
        }

        foreach ($this->resolveToolbarActions($resource) as $action) {
            if ($action instanceof ToolbarAction && $action->isVisible(['resource' => $resource, 'grid' => $grid])) {
                $this->addActionButton($action, $grid);
            }
        }

        // Экспорт управляется единым флагом exportEnabled() (по умолчанию выключен).
        if (!method_exists($resource, 'exportEnabled') || $resource->exportEnabled()) {
            Toolbar::addButton(
                new Button([
                    'text' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_EXPORT_CSV', 'Export CSV'),
                    'click' => new JsCode('window.location.href=' . json_encode($this->exportUrl($grid), JSON_UNESCAPED_SLASHES) . ';'),
                ]),
                ButtonLocation::AFTER_TITLE,
            );
        }

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    /**
     * Фичи нативного тулбара поверх кнопок: заголовок, редактируемый заголовок,
     * избранное, копировать-ссылку, кастомные HTML-слоты. Драйвятся хуками ресурса.
     */
    private function applyToolbarFeatures(CrudResourceContract $resource): void
    {
        if (method_exists($resource, 'toolbarTitle') && ($title = $resource->toolbarTitle()) !== null) {
            Toolbar::setTitle($title);
        }

        if (method_exists($resource, 'toolbarEditableTitle') && $resource->toolbarEditableTitle()) {
            Toolbar::addEditableTitle();
        }

        if (method_exists($resource, 'toolbarFavoriteStar') && $resource->toolbarFavoriteStar()) {
            Toolbar::addFavoriteStar();
        }

        if (method_exists($resource, 'toolbarCopyLink') && ($copyLink = $resource->toolbarCopyLink()) !== null) {
            Toolbar::setCopyLinkButton($copyLink);
        }

        if (method_exists($resource, 'toolbarBeforeTitleHtml') && ($html = $resource->toolbarBeforeTitleHtml()) !== null) {
            Toolbar::addBeforeTitleHtml($html);
        }

        if (method_exists($resource, 'toolbarAfterTitleHtml') && ($html = $resource->toolbarAfterTitleHtml()) !== null) {
            Toolbar::addAfterTitleHtml($html);
        }

        if (method_exists($resource, 'toolbarUnderTitleHtml') && ($html = $resource->toolbarUnderTitleHtml()) !== null) {
            Toolbar::addUnderTitleHtml($html);
        }

        if (method_exists($resource, 'toolbarRightHtml') && ($html = $resource->toolbarRightHtml()) !== null) {
            Toolbar::addRightCustomHtml($html);
        }
    }

    /**
     * Превращает ToolbarAction в кнопку тулбара: простую, с выпадающим меню или split.
     */
    private function addActionButton(ToolbarAction $action, Grid $grid): void
    {
        $location = $action->getLocation();

        if ($action->hasMenu() && $action->isSplit()) {
            Toolbar::addButton($this->buildSplitButton($action, $grid), $location);

            return;
        }

        if ($action->hasMenu()) {
            Toolbar::addButton($this->buildDropdownButton($action, $grid), $location);

            return;
        }

        Toolbar::addButton($this->buildSimpleButton($action, $grid), $location);
    }

    private function buildSimpleButton(ToolbarAction $action, Grid $grid): Button
    {
        return new Button($this->baseButtonOptions($action) + [
            'click' => new JsCode($this->actionClickJs($action, $grid)),
        ]);
    }

    private function buildDropdownButton(ToolbarAction $action, Grid $grid): Button
    {
        $button = new Button($this->baseButtonOptions($action) + ['dropdown' => true]);
        $button->setMenu(['items' => $this->menuItems($action, $grid)]);

        return $button;
    }

    private function buildSplitButton(ToolbarAction $action, Grid $grid): SplitButton
    {
        $button = new SplitButton([
            'mainButton' => array_filter([
                'text' => $action->getLabel(),
                'color' => $action->getColor(),
                'icon' => $action->getIcon(),
                'counter' => $action->getCounter(),
                'click' => new JsCode($this->actionClickJs($action, $grid)),
            ]),
            'menuButton' => array_filter([
                'color' => $action->getColor(),
            ]),
            'menuTarget' => SplitType::MENU,
        ]);

        if ($action->isDisabled()) {
            $button->setDisabled();
        }
        $button->setMenu(['items' => $this->menuItems($action, $grid)]);

        return $button;
    }

    /** @return array<string,mixed> */
    private function baseButtonOptions(ToolbarAction $action): array
    {
        return array_filter([
            'text' => $action->getLabel(),
            'color' => $action->getColor(),
            'icon' => $action->getIcon(),
            'counter' => $action->getCounter(),
            'size' => $action->getSize(),
            'state' => $action->isDisabled() ? State::DISABLED : null,
            'round' => $action->isRound() ? true : null,
            'collapsedIcon' => $action->getCollapsedIcon(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * Пункты выпадающего меню в формате main.popup Menu (text + href|onclick).
     *
     * @return array<int,array<string,mixed>>
     */
    private function menuItems(ToolbarAction $action, Grid $grid): array
    {
        $items = [];
        foreach ($action->getItems() as $item) {
            if (!$item->isVisible()) {
                continue;
            }

            $entry = ['text' => $item->getLabel()];
            $click = $this->actionClickJs($item, $grid, false);
            if ($click !== null) {
                // BX.UI.ButtonManager ожидает обработчик как объект { code: '<js>' }, а не строку.
                $entry['onclick'] = ['code' => $click];
            } else {
                $entry['href'] = $item->getUrl();
            }
            $items[] = $entry;
        }

        return $items;
    }

    /**
     * JS клика по действию. $fallbackToHref=false возвращает null, если переход — это просто ссылка
     * (для пунктов меню, где href задаётся отдельно).
     */
    private function actionClickJs(ToolbarAction $action, Grid $grid, bool $fallbackToHref = true): ?string
    {
        if ($action->getSidePanel() !== null) {
            return $this->sidePanelOpenJs($action->getUrl(), $action->getSidePanel(), $grid);
        }

        if ($action->getOnclick() !== null) {
            return $action->getOnclick();
        }

        if (!$fallbackToHref) {
            return null;
        }

        return 'window.location.href=' . json_encode($action->getUrl(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';';
    }

    /**
     * JS открытия URL в слайдере (side-panel) с перезагрузкой грида при закрытии.
     *
     * @param array{width?:int,gridId?:string|null} $config
     */
    private function sidePanelOpenJs(string $url, array $config, Grid $grid): string
    {
        $width = (int)($config['width'] ?? 1100);
        $gridId = $config['gridId'] ?? $grid->getId();
        $urlWithIframe = (new UrlGenerator($url))->with(['IFRAME' => 'Y']);

        $urlJson = json_encode($urlWithIframe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $gridJson = json_encode($gridId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return "BX.SidePanel.Instance.open({$urlJson}, {"
            . "width: {$width}, cacheable: false, allowChangeHistory: false,"
            . "events: { onClose: function() {"
            . "if (BX.Main && BX.Main.gridManager) { var grid = BX.Main.gridManager.getInstanceById({$gridJson}); if (grid) { grid.reload(); } }"
            . "} } });";
    }

    public function renderForm(CrudResourceContract $resource, string $formId, string $cancelJs): void
    {
        global $APPLICATION;
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
        }

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    public function renderDetail(CrudResourceContract $resource, string $backJs, ?string $editUrl = null): void
    {
        global $APPLICATION;
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
        }

        if ($editUrl !== null && $resource->canUpdate(new PermissionContext(resource: $resource, operation: 'update'))) {
            Toolbar::addButton(
                new Button([
                    'text' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_EDIT', 'Edit'),
                    'click' => new JsCode('window.location.href=' . json_encode($editUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';'),
                ]),
                ButtonLocation::AFTER_TITLE,
            );
        }

        Toolbar::addButton(
            new Button([
                'text' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_BACK', 'Back'),
                'click' => new JsCode($backJs),
            ]),
            ButtonLocation::AFTER_TITLE,
        );

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    public function createButtonJs(CrudResourceContract $resource, Grid $grid, string $createUrl): string
    {
        $adapter = new SidePanelAdapter($resource);

        if (!$adapter->shouldOpen('create')) {
            return 'window.location.href=' . json_encode($createUrl, JSON_UNESCAPED_SLASHES) . ';';
        }

        return $adapter->openJs($createUrl, $grid->getId());
    }


    private function exportUrl(Grid $grid): string
    {
        $query = [
            'action' => 'export',
            'grid_id' => $grid->getId(),
        ];

        return $this->buildUrl($query);
    }

    /** @param array<string,string> $params */
    private function buildUrl(array $params): string
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $parsed = parse_url($requestUri);
        parse_str($parsed['query'] ?? '', $query);
        $query = array_replace($query, $params);
        $path = (string)($parsed['path'] ?? '');

        return $path . '?' . http_build_query($query);
    }

    /** @return iterable<ToolbarAction|string> */
    private function resolveToolbarActions(CrudResourceContract $resource): iterable
    {
        if (method_exists($resource, 'toolbarActions')) {
            $actions = $resource->toolbarActions();
            if (is_iterable($actions)) {
                return $actions;
            }
        }

        return ['export'];
    }

}
