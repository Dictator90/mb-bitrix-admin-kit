<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Grouping;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

final class GroupLabelRenderer
{
    /** @param array<string,mixed> $rowData */
    public function render(array $rowData, IndexGrouping $grouping, string $baseUrl): string
    {
        $groupId = $rowData['__GROUP_ID'] ?? null;
        $groupData = is_array($rowData['__GROUP_DATA'] ?? null) ? $rowData['__GROUP_DATA'] : [];
        $resourceClass = $rowData['__GROUP_RESOURCE'] ?? $grouping->resourceClass();
        $resource = new $resourceClass();
        if (!$resource instanceof ResourceContract || $groupId === null || $groupId === '__ungrouped') {
            return htmlspecialchars((string)$this->rawLabel($rowData, $grouping, $groupData));
        }

        $url = (new UrlGenerator($baseUrl))->editResourceUrl($resource, $groupId);
        $label = htmlspecialchars((string)$this->rawLabel($rowData, $grouping, $groupData));
        $href = htmlspecialchars($url);
        $onclick = '';
        if (method_exists($resource, 'editInSidePanel') && $resource->editInSidePanel()) {
            $width = method_exists($resource, 'sidePanelWidth') ? (int)$resource->sidePanelWidth() : 1100;
            $onclick = ' onclick="BX.SidePanel.Instance.open(this.href, {width: ' . $width . '}); return false;"';
        }

        return '<a href="' . $href . '"' . $onclick . '>' . $label . '</a>';
    }

    /**
     * @param array<string,mixed> $rowData
     * @param array<string,mixed> $groupData
     */
    private function rawLabel(array $rowData, IndexGrouping $grouping, array $groupData): mixed
    {
        $labelColumn = $grouping->labelColumn();
        if ($labelColumn !== null && array_key_exists($labelColumn, $rowData)) {
            return $rowData[$labelColumn];
        }
        $label = $grouping->label();
        if ($label instanceof \Closure) {
            return $label($groupData);
        }
        if (is_string($label)) {
            return $groupData[$label] ?? '';
        }

        return '';
    }
}
