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
        $headerRenderer = new TabHeaderRenderer();
        $items = [];
        foreach ($tabs as $sort => $tab) {
            $items[] = [
                'id' => $tab->getId(),
                'sort' => $sort,
                'active' => $tab->isActive(),
                'head' => $tab->getHeadOptions(),
                'body' => $bodyRenderer->render($tab, $context),
                'tab' => $tab,
            ];
        }

        $jsItems = [];
        $headersHtml = '';
        $bodiesHtml = '';
        foreach ($items as $item) {
            $tab = $item['tab'];
            assert($tab instanceof Tab);

            $headersHtml .= $headerRenderer->render($tab);

            $activeClass = $item['active'] ? ' --body-active' : '';
            $tabId = htmlspecialchars($item['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $bodiesHtml .= '<div class="ui-tabs__tab-body_inner' . $activeClass
                . '" data-id="' . $tabId . '" data-role="body">'
                . '<div class="ui-tabs__tab-body_data">' . $item['body'] . '</div>'
                . '</div>';

            unset($item['body'], $item['tab']);
            $jsItems[] = $item;
        }

        $cid = htmlspecialchars($config->containerId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $jsConfig = json_encode([
            'id' => $config->containerId,
            'items' => $jsItems,
            'remember' => $config->remember,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

        return <<<HTML
        <div
            id="{$cid}"
            data-adminkit-tabs
            data-adminkit-tabs-prerendered="Y"
            data-adminkit-tabs-config='{$jsConfig}'
        >
            <div class="ui-tabs__tabs-container">
                <div class="ui-tabs__tabs-header-container" data-bx-role="headers">{$headersHtml}</div>
                <div class="ui-tabs__tabs-body-container" data-bx-role="bodies">{$bodiesHtml}</div>
            </div>
        </div>
        <script>
        BX.ready(function() {
            var el = document.getElementById('{$cid}');
            if (!el) {
                return;
            }

            if (el.dataset.adminkitTabsPrerendered === 'Y') {
                var root = el.querySelector('.ui-tabs__tabs-container') || el;
                var activateTab = function(tabId) {
                    root.querySelectorAll('.ui-tabs__tab-body_inner').forEach(function(body) {
                        body.classList.toggle('--body-active', body.getAttribute('data-id') === tabId);
                    });
                    root.querySelectorAll('[data-bx-role="tab-header"]').forEach(function(header) {
                        header.classList.toggle('--header-active', header.getAttribute('data-bx-name') === tabId);
                    });
                };
                root.querySelectorAll('[data-bx-role="tab-header"]').forEach(function(header) {
                    header.addEventListener('click', function() {
                        var tabId = header.getAttribute('data-bx-name') || '';
                        if (tabId !== '') {
                            activateTab(tabId);
                            var activeTabInput = document.querySelector('input[name="adminkit_active_tab"]');
                            if (activeTabInput) {
                                activeTabInput.value = tabId;
                            }
                        }
                    });
                });
                if (window.BX && window.BX.UI && window.BX.UI.Hint) {
                    window.BX.UI.Hint.init(root);
                }
                return;
            }

            BX.Runtime.loadExtension('mb.admin.kit').then(function(kit) {
                if (kit && kit.Tabs && kit.Tabs.initAll) {
                    kit.Tabs.initAll(el);
                }
            });
        });
        </script>
        HTML;
    }
}
