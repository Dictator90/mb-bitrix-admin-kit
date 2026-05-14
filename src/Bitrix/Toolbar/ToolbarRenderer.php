<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Toolbar;

use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Security\PermissionContext;

final class ToolbarRenderer
{
    public function render(ResourceContract $resource, Grid $grid, string $createUrl): void
    {
        global $APPLICATION;

        if ($filterParams = $grid->getFilterComponentParams()) {
            Toolbar::addFilter($filterParams);
        }

        if ($resource->canCreate(new PermissionContext(resource: $resource, operation: 'create'))) {
            Toolbar::addButton(
                new Button([
                    'color' => Color::SUCCESS,
                    'icon' => Icon::ADD,
                    'text' => 'Создать',
                    'click' => new JsCode($this->createButtonJs($resource, $grid, $createUrl)),
                ]),
                ButtonLocation::AFTER_TITLE,
            );
        }

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    public function createButtonJs(ResourceContract $resource, Grid $grid, string $createUrl): string
    {
        if (method_exists($resource, 'createInSidePanel') && !$resource->createInSidePanel()) {
            return 'window.location.href=' . json_encode($createUrl, JSON_UNESCAPED_SLASHES) . ';';
        }

        $options = [
            'events' => [
                'onCloseComplete' => '__ADMIN_KIT_RELOAD_GRID__',
            ],
        ];

        if (method_exists($resource, 'sidePanelWidth')) {
            $options['width'] = $resource->sidePanelWidth();
        }

        $json = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $json = str_replace('"__ADMIN_KIT_RELOAD_GRID__"', 'function(){' . $this->reloadGridJs($grid) . '}', (string)$json);

        return 'BX.SidePanel.Instance.open(' . json_encode($createUrl) . ', ' . $json . ')';
    }

    private function reloadGridJs(Grid $grid): string
    {
        return 'var grid=BX.Main.gridManager.getInstanceById(' . json_encode($grid->getId()) . ');'
            . 'if(grid){grid.reload();}';
    }
}
