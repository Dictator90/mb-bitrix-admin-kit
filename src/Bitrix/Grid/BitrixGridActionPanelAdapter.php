<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Grid;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet as GridPanelSnippet;
use Bitrix\Main\Grid\Panel\Types;
use LogicException;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use MB\Bitrix\AdminKit\Grid\Grid;

final class BitrixGridActionPanelAdapter
{
    /** @return array<string,mixed> */
    public function componentParams(Grid $grid): array
    {
        return ['GROUPS' => $this->buildGroups($grid)];
    }

    /** @return array<int,array<string,mixed>> */
    private function buildGroups(Grid $grid): array
    {
        $groups = [];
        $actionsByGroup = [];

        $defaultItems = [];
        if ($grid->hasEditableFields()) {
            $defaultItems[] = (new GridPanelSnippet())->getEditButton();
        }

        if ($grid->shouldShowSelectAllRecordsCheckbox()) {
            $defaultItems[] = (new GridPanelSnippet())->getForAllCheckbox();
        }

        $this->validateUniqueActionIds($grid);

        foreach ($this->visiblePanelItems($grid) as $item) {
            $groupName = $item->getGroup();
            $actionsByGroup[$groupName][] = $item;
        }

        if (isset($actionsByGroup['default'])) {
            usort($actionsByGroup['default'], static function (BulkPanelItemContract $a, BulkPanelItemContract $b): int {
                return $a->getSort() <=> $b->getSort();
            });

            foreach ($actionsByGroup['default'] as $item) {
                $panelItem = $this->buildPanelItem($grid, $item);
                if ($panelItem !== []) {
                    $defaultItems[] = $panelItem;
                }
            }
            unset($actionsByGroup['default']);
        }

        if ($defaultItems !== []) {
            $groups[] = [
                'ITEMS' => $defaultItems,
            ];
        }

        uksort($actionsByGroup, static function (string $leftKey, string $rightKey) use ($actionsByGroup): int {
            $leftItem = $actionsByGroup[$leftKey][0] ?? null;
            $rightItem = $actionsByGroup[$rightKey][0] ?? null;
            $leftSort = $leftItem instanceof BulkPanelItemContract ? $leftItem->getGroupSort() : 100;
            $rightSort = $rightItem instanceof BulkPanelItemContract ? $rightItem->getGroupSort() : 100;

            return ($leftSort <=> $rightSort) ?: ($leftKey <=> $rightKey);
        });

        foreach ($actionsByGroup as $groupName => $items) {
            usort($items, static function (BulkPanelItemContract $a, BulkPanelItemContract $b): int {
                return $a->getSort() <=> $b->getSort();
            });

            $panelItems = [];
            foreach ($items as $item) {
                $panelItem = $this->buildPanelItem($grid, $item);
                if ($panelItem !== []) {
                    $panelItems[] = $panelItem;
                }
            }

            if ($panelItems !== []) {
                $groups[] = ['ITEMS' => $panelItems];
            }
        }

        return $groups;
    }

    /** @return list<BulkPanelItemContract> */
    private function visiblePanelItems(Grid $grid): array
    {
        $items = [];

        foreach ($grid->getBulkActions() as $item) {
            if ($item instanceof BulkActionDropdown) {
                if ($this->visibleDropdownItems($item) !== []) {
                    $items[] = $item;
                }
                continue;
            }

            if ($item instanceof BulkAction && $item->isVisible()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /** @return list<BulkAction> */
    private function visibleDropdownItems(BulkActionDropdown $dropdown): array
    {
        $items = [];

        foreach ($dropdown->getItems() as $action) {
            if ($action->isVisible()) {
                $items[] = $action;
            }
        }

        return $items;
    }

    private function buildPanelItem(Grid $grid, BulkPanelItemContract $item): array
    {
        if ($item instanceof BulkActionDropdown) {
            return $this->buildBulkActionDropdown($grid, $item);
        }

        if ($item instanceof BulkAction) {
            return $this->buildBulkActionButton($grid, $item);
        }

        throw new LogicException(sprintf('Unsupported bulk panel item type: %s', get_debug_type($item)));
    }

    /** @return array<string,mixed> */
    private function buildBulkActionButton(Grid $grid, BulkAction $action): array
    {
        if ($action->hasCustomPanelItem()) {
            return $action->getCustomPanelItem($grid) ?? [];
        }

        $class = trim(implode(' ', array_filter([
            $action->getButtonClass(),
            $action->getIcon(),
            $action->isDanger() ? 'ui-btn-danger' : null,
        ])));

        $item = [
            'TYPE' => $action->getPanelType(),
            'ID' => $action->getId(),
            'NAME' => $action->getId(),
            'TEXT' => $action->getLabel(),
            'TITLE' => $action->getTitle(),
            'CLASS' => $class,
            'ONCHANGE' => [[
                'ACTION' => Actions::CALLBACK,
                'DATA' => [[
                    'JS' => $this->buildBulkActionCallbackJs($grid, $action),
                ]],
            ]],
        ];

        if ($action->needsConfirm()) {
            $item['ONCHANGE'][0]['CONFIRM'] = true;
            $item['ONCHANGE'][0]['CONFIRM_MESSAGE'] = $action->getConfirmText() ?? 'Are you sure?';
        }

        return $item;
    }

    /** @return array<string,mixed> */
    private function buildBulkActionDropdown(Grid $grid, BulkActionDropdown $dropdown): array
    {
        $visibleActions = $this->visibleDropdownItems($dropdown);
        if ($visibleActions === []) {
            return [];
        }

        $items = [];
        if ($dropdown->shouldShowPlaceholder()) {
            $items[] = [
                'NAME' => $dropdown->getPlaceholder(),
                'VALUE' => $dropdown->getPlaceholderValue(),
            ];
        }

        foreach ($visibleActions as $action) {
            $items[] = $this->buildDropdownItem($grid, $action);
        }

        $control = [
            'TYPE' => Types::DROPDOWN,
            'ID' => $dropdown->getId(),
            'NAME' => strtoupper($dropdown->getId()),
            'MULTIPLE' => $dropdown->isMultiple() ? 'Y' : 'N',
            'ITEMS' => $items,
        ];

        if ($dropdown->getTitle() !== null) {
            $control['TITLE'] = $dropdown->getTitle();
        }

        $class = trim(implode(' ', array_filter([
            $dropdown->getButtonClass(),
            $dropdown->getIcon(),
        ])));

        if ($class !== '') {
            $control['CLASS'] = $class;
        }

        return $control;
    }

    /** @return array<string,mixed> */
    private function buildDropdownItem(Grid $grid, BulkAction $action): array
    {
        $item = [
            'NAME' => $action->getLabel(),
            'VALUE' => $action->getId(),
            'ONCHANGE' => [[
                'ACTION' => Actions::CALLBACK,
                'DATA' => [[
                    'JS' => $this->buildBulkActionCallbackJs($grid, $action),
                ]],
            ]],
        ];

        if ($action->needsConfirm()) {
            $item['ONCHANGE'][0]['CONFIRM'] = true;
            $item['ONCHANGE'][0]['CONFIRM_MESSAGE'] = $action->getConfirmText() ?? 'Are you sure?';
        }

        return $item;
    }

    private function validateUniqueActionIds(Grid $grid): void
    {
        $ids = [];
        foreach ($grid->getExecutableBulkActions() as $action) {
            $id = $action->getId();
            if (isset($ids[$id])) {
                throw new LogicException(sprintf(
                    'Duplicate bulk action id [%s]. Bulk action IDs must be unique, including dropdown items.',
                    $id
                ));
            }
            $ids[$id] = true;
        }
    }

    public function buildBulkActionCallbackJs(Grid $grid, BulkAction|string $action): string
    {
        $actionId = $action instanceof BulkAction ? $action->getId() : $action;
        $method = $action instanceof BulkAction ? $action->getClientHandler() : 'runBulkAction';
        $method = preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $method) ? $method : 'runBulkAction';

        $config = [
            'gridId' => $grid->getId(),
            'actionId' => $actionId,
            'actionButtonKey' => 'action_button_' . $grid->getId(),
            'forAllKey' => 'action_all_rows_' . $grid->getId(),
        ];

        $jsonConfig = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

        return "BX.Runtime.loadExtension('mb.admin.kit').then(function(kit){" .
            "if(kit&&kit.GridBulkActions&&kit.GridBulkActions.{$method}){" .
            "kit.GridBulkActions.{$method}({$jsonConfig});" .
            '}' .
            '});';
    }
}
