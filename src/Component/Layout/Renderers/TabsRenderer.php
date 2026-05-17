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
        $jsItemsJson = json_encode($jsItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        $bodyInjectJson = json_encode($bodyInjects, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        $rememberJson = json_encode($config->remember, JSON_UNESCAPED_UNICODE);

        return <<<HTML
        <div id="{$cid}"></div>
        <script>
        BX.ready(function() {
            var run = function() {
                if (!MB.AdminKit || !MB.AdminKit.Tabs || !MB.AdminKit.Tabs.Tabs) {
                    return;
                }
                var tabs = new MB.AdminKit.Tabs.Tabs({ id: '{$cid}', items: {$jsItemsJson} });
                var container = tabs.getContainer();
                var bodies = {$bodyInjectJson};
                for (var i = 0; i < bodies.length; i++) {
                    var bodyData = container.querySelector('.ui-tabs__tab-body_inner[data-id="' + bodies[i].id + '"] .ui-tabs__tab-body_data');
                    if (bodyData) {
                        bodyData.innerHTML = bodies[i].html;
                        bodyData.querySelectorAll('script').forEach(function(oldScript) {
                            var s = document.createElement('script');
                            s.textContent = oldScript.textContent;
                            oldScript.parentNode.replaceChild(s, oldScript);
                        });
                    }
                    if (bodies[i].active) {
                        var bodyInner = container.querySelector('.ui-tabs__tab-body_inner[data-id="' + bodies[i].id + '"]');
                        var header = container.querySelector('[data-bx-name="' + bodies[i].id + '"]');
                        if (bodyInner) { bodyInner.classList.add('--body-active'); }
                        if (header) { header.classList.add('--header-active'); }
                    }
                }
                BX.Dom.append(container, document.getElementById('{$cid}'));
                if (BX.UI && BX.UI.Hint) {
                    BX.UI.Hint.init(container);
                }
                if ({$rememberJson}) {
                    var activeTabInput = document.querySelector('input[name="adminkit_active_tab"]');
                    container.addEventListener('click', function(event) {
                        var header = event.target.closest('[data-bx-name]');
                        if (!header || !activeTabInput) {
                            return;
                        }
                        var tabId = header.getAttribute('data-bx-name') || '';
                        if (tabId !== '') {
                            activeTabInput.value = tabId;
                        }
                    });
                }
            };
            if (BX.Runtime && BX.Runtime.loadExtension) {
                BX.Runtime.loadExtension('mb.admin.kit').then(run).catch(run);
            } else {
                run();
            }
        });
        </script>
        HTML;
    }
}
