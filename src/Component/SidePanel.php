<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

class SidePanel
{
    public static function open(string $url, array $options = []): string
    {
        $jsUrl = \CUtil::JSEscape($url);
        $width = (int)($options['width'] ?? 1100);
        $loader = \CUtil::JSEscape($options['loader'] ?? 'default-loader');
        $gridId = isset($options['reloadGridId']) ? \CUtil::JSEscape($options['reloadGridId']) : '';

        $reloadCallback = '';
        if ($gridId) {
            $reloadCallback = <<<JS
            events: {
                onCloseStart: function(event) {
                    var grid = BX.Main.gridManager.getInstanceById('{$gridId}');
                    if (grid) grid.reload();
                }
            },
            JS;
        }

        return <<<JS
        BX.SidePanel.Instance.open('{$jsUrl}', {
            width: {$width},
            loader: '{$loader}',
            {$reloadCallback}
        });
        JS;
    }

    public static function close(): string
    {
        return 'BX.SidePanel.Instance.close();';
    }

    public static function reload(): string
    {
        return 'BX.SidePanel.Instance.reload();';
    }

    /**
     * Returns JS that reloads the parent grid when a SidePanel form is saved.
     * Call this from within an inline script in the side panel page.
     */
    public static function notifyParentGrid(string $gridId): string
    {
        $jsGridId = \CUtil::JSEscape($gridId);

        return <<<JS
        if (window.parent && window.parent.BX) {
            var grid = window.parent.BX.Main.gridManager.getInstanceById('{$jsGridId}');
            if (grid) grid.reload();
        }
        JS;
    }

    /**
     * Renders a <script> tag that automatically closes the side panel after save.
     * Pass saved=1 query param to trigger this.
     */
    public static function closeOnSaved(): string
    {
        return <<<HTML
        <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('saved') === '1' && window.parent && window.parent.BX) {
                window.parent.BX.SidePanel.Instance.close();
            }
        })();
        </script>
        HTML;
    }
}
