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

        Extension::load([$config->extension]);

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

        $cid = htmlspecialchars($config->containerId, ENT_QUOTES);
        $ext = json_encode($config->extension, JSON_UNESCAPED_UNICODE);
        $jsItemsJson = json_encode($jsItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        $bodyInjectJson = json_encode($bodyInjects, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        $rememberJson = json_encode($config->remember, JSON_UNESCAPED_UNICODE);

        return <<<HTML
        <div id="{$cid}" data-adminkit-tabs-config="{$cid}"></div>
        <script>
        BX.ready(function() {
            BX.Runtime.loadExtension({$ext}).then(function(m) {
                var tabs = new m.Tabs({ id: '{$cid}', items: {$jsItemsJson} });
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
                        var header    = container.querySelector('[data-bx-name="' + bodies[i].id + '"]');
                        if (bodyInner) bodyInner.classList.add('--body-active');
                        if (header) header.classList.add('--header-active');
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
            });
        });
        </script>
        HTML;
    }
}
