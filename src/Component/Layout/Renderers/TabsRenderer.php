<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout\Renderers;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\TabsConfig;

final class TabsRenderer
{
    /** @param list<Tab> $tabs */
    public function render(array $tabs, TabsConfig $config, ComponentContext $context): string
    {
        if ($tabs === []) {
            return '';
        }

        if (class_exists(Extension::class)) {
            Extension::load(['mb.admin.kit']);
        }

        $bodyRenderer = new TabBodyRenderer();
        $items = [];
        foreach ($tabs as $sort => $tab) {
            $items[] = [
                'id' => $tab->getId(),
                'sort' => $sort,
                'active' => $tab->isActive(),
                'head' => $tab->getHeadOptions(),
                'body' => $bodyRenderer->render($tab, $context),
            ];
        }

        $jsItems = [];
        $bodyInjects = [];
        foreach ($items as $item) {
            $bodyInjects[] = [
                'id' => $item['id'],
                'html' => $item['body'],
                'active' => $item['active'],
            ];
            unset($item['body']);
            $jsItems[] = $item;
        }

        $cid = htmlspecialchars($config->containerId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $jsConfig = json_encode([
            'id' => $config->containerId,
            'items' => $jsItems,
            'bodies' => $bodyInjects,
            'remember' => $config->remember,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

        return <<<HTML
        <div
            id="{$cid}"
            data-adminkit-tabs
            data-adminkit-tabs-config='{$jsConfig}'
        ></div>
        <script>
        BX.ready(function() {
            BX.Runtime.loadExtension('mb.admin.kit').then(function(kit) {
                if (kit && kit.Tabs && kit.Tabs.initAll) {
                    kit.Tabs.initAll(document.getElementById('{$cid}'));
                }
            });
        });
        </script>
        HTML;
    }
}
