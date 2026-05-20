<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Toolbar;

use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Manager\SidePanelAdapter;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

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

        if ($resource->canCreate(new PermissionContext(resource: $resource, operation: 'create'))) {
            Toolbar::addButton(
                new Button([
                    'color' => Color::SUCCESS,
                    'icon' => Icon::ADD,
                    'text' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_CREATE', 'Create'),
                    'click' => new JsCode($this->createButtonJs($resource, $grid, $createUrl)),
                ]),
                ButtonLocation::AFTER_TITLE,
            );
        }

        foreach ($this->resolveToolbarActions($resource) as $action) {
            if ($action === 'export') {
                Toolbar::addButton(
                    new Button([
                        'text' => LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_TOOLBAR_EXPORT_CSV', 'Export CSV'),
                        'click' => new JsCode('window.location.href=' . json_encode($this->exportUrl($grid), JSON_UNESCAPED_SLASHES) . ';'),
                    ]),
                    ButtonLocation::AFTER_TITLE,
                );
                continue;
            }

            if ($action instanceof ToolbarAction && $action->isVisible(['resource' => $resource, 'grid' => $grid])) {
                Toolbar::addButton(
                    new Button([
                        'text' => $action->getLabel(),
                        'click' => new JsCode('window.location.href=' . json_encode($action->getUrl(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';'),
                    ]),
                    ButtonLocation::AFTER_TITLE,
                );
            }
        }

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
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

    private function reloadGridJs(Grid $grid): string
    {
        return 'var grid=BX.Main.gridManager.getInstanceById(' . json_encode($grid->getId()) . ');'
            . 'if(grid){grid.reload();}';
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
