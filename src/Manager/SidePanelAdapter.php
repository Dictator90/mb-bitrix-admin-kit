<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

final class SidePanelAdapter
{
    public function __construct(private ResourceContract $resource, private ?UrlGenerator $urls = null)
    {
    }

    public function shouldOpen(string $action): bool
    {
        return match ($action) {
            'add', 'create' => $this->resourceFlag('createInSidePanel'),
            'edit' => $this->resourceFlag('editInSidePanel'),
            'detail', 'view' => $this->resourceFlag('detailInSidePanel'),
            default => false,
        };
    }

    public function iframeParams(array $params = []): array
    {
        return ['IFRAME' => 'Y'] + $params;
    }

    public function openJs(string $url, ?string $gridId = null): string
    {
        $url = (new UrlGenerator($url))->with($this->iframeParams());
        $options = [
            'width' => $this->sidePanelWidth(),
            'cacheable' => false,
            'allowChangeHistory' => false,
        ];
        if ($gridId !== null && $gridId !== '') {
            $options['events'] = [
                'onClose' => "reloadGrid:{$gridId}",
            ];
        }

        return 'BX.SidePanel.Instance.open(' . json_encode($url) . ', ' . $this->encodeOptions($options, $gridId) . ');';
    }

    public function closeAfterSaveScript(?string $gridId = null): string
    {
        $gridIdJs = json_encode($gridId ?? $this->resource->getGridId());

        return <<<HTML
        <script>
        BX.ready(function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('saved') !== '1' || !window.parent || !window.parent.BX) return;
            var gridId = {$gridIdJs};
            if (window.parent.BX.Main && window.parent.BX.Main.gridManager) {
                var grid = window.parent.BX.Main.gridManager.getInstanceById(gridId);
                if (grid) grid.reload();
            }
            if (window.parent.BX.SidePanel) {
                window.parent.BX.SidePanel.Instance.close();
            }
        });
        </script>
        HTML;
    }

    public function sidePanelId(string $action, mixed $id = null): string
    {
        return AdminString::id('adminkit_sidepanel', $this->resource::getId() . '_' . $action . '_' . (string)$id);
    }

    private function resourceFlag(string $method): bool
    {
        if (method_exists($this->resource, $method)) {
            return (bool)$this->resource->{$method}();
        }

        return method_exists($this->resource, 'useSidePanel') && (bool)$this->resource->useSidePanel();
    }

    private function sidePanelWidth(): int
    {
        return method_exists($this->resource, 'sidePanelWidth') ? (int)$this->resource->sidePanelWidth() : 1100;
    }

    private function encodeOptions(array $options, ?string $gridId): string
    {
        $json = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        if ($gridId === null || $gridId === '') {
            return $json;
        }

        $grid = json_encode($gridId);
        return <<<JS
        {
            width: {$options['width']},
            cacheable: false,
            allowChangeHistory: false,
            events: {
                onClose: function() {
                    if (BX.Main && BX.Main.gridManager) {
                        var grid = BX.Main.gridManager.getInstanceById({$grid});
                        if (grid) grid.reload();
                    }
                }
            }
        }
        JS;
    }
}
