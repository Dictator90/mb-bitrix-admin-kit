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
                    'JS' => $this->buildBulkActionCallbackJs($grid, $action->getId()),
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
                    'JS' => $this->buildBulkActionCallbackJs($grid, $action->getId()),
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

    private function jsEscape(string $value): string
    {
        return addslashes($value);
    }

    public function buildBulkActionCallbackJs(Grid $grid, string $actionId): string
    {
        if ($actionId === 'export_selected') {
            return $this->buildBulkExportCallbackJs($grid, $actionId);
        }

        $gridIdJs = $this->jsEscape($grid->getId());
        $actionIdJs = $this->jsEscape($actionId);
        $actionButtonKeyJs = $this->jsEscape('action_button_' . $grid->getId());
        $forAllKeyJs = 'action_all_rows_' . $gridIdJs;

        return
            '(function(){' .
                "var manager=BX.Main.gridManager&&BX.Main.gridManager.getById('{$gridIdJs}');" .
                'var grid=manager&&(manager.instance||manager.grid);' .
                'if(!grid){return;}' .
                "var rows=(typeof grid.getRows==='function')?grid.getRows():null;" .
                "var ids=(rows&&typeof rows.getSelectedIds==='function')?rows.getSelectedIds():[];" .
                "var panel=(typeof grid.getActionsPanel==='function')?grid.getActionsPanel():null;" .
                "var values=(panel&&typeof panel.getValues==='function')?panel.getValues():{};" .
                "var forAll=(values&&values['{$forAllKeyJs}']==='Y')?'Y':'N';" .
                "if((!ids||ids.length===0)&&forAll!=='Y'){" .
                    'if(BX.UI&&BX.UI.Notification&&BX.UI.Notification.Center){' .
                        "BX.UI.Notification.Center.notify({content:'Select at least one row'});" .
                    '}' .
                    'return;' .
                '}' .
                'var data={};' .
                "data['{$actionButtonKeyJs}']='{$actionIdJs}';" .
                "data['{$forAllKeyJs}']=forAll;" .
                "if(BX&&typeof BX.bitrix_sessid==='function'){" .
                    "data['sessid']=BX.bitrix_sessid();" .
                '}' .
                'data.ID=ids;' .
                'data.id=ids;' .
                'data.rows=ids;' .
                "if(typeof grid.reloadTable==='function'){" .
                    "grid.reloadTable('POST',data);" .
                '}' .
            '})();';
    }

    private function buildBulkExportCallbackJs(Grid $grid, string $actionId): string
    {
        $gridIdJs = $this->jsEscape($grid->getId());
        $actionIdJs = $this->jsEscape($actionId);
        $forAllKeyJs = 'action_all_rows_' . $gridIdJs;

        return
            '(function(){' .
                "var manager=BX.Main.gridManager&&BX.Main.gridManager.getById('{$gridIdJs}');" .
                'var grid=manager&&(manager.instance||manager.grid);' .
                'if(!grid){return;}' .
                "var rows=(typeof grid.getRows==='function')?grid.getRows():null;" .
                "var ids=(rows&&typeof rows.getSelectedIds==='function')?rows.getSelectedIds():[];" .
                'if(!ids||ids.length===0){' .
                    'if(BX.UI&&BX.UI.Notification&&BX.UI.Notification.Center){' .
                        "BX.UI.Notification.Center.notify({content:'Select at least one row'});" .
                    '}' .
                    'return;' .
                '}' .
                "var form=document.createElement('form');" .
                "form.method='POST';" .
                'form.action=window.location.pathname+window.location.search;' .
                "var action=document.createElement('input');" .
                "action.type='hidden';action.name='action';action.value='{$actionIdJs}';form.appendChild(action);" .
                "var forAll=document.createElement('input');" .
                "forAll.type='hidden';forAll.name='{$forAllKeyJs}';forAll.value='N';form.appendChild(forAll);" .
                "if(BX&&typeof BX.bitrix_sessid==='function'){" .
                    "var sessid=document.createElement('input');" .
                    "sessid.type='hidden';sessid.name='sessid';sessid.value=BX.bitrix_sessid();form.appendChild(sessid);" .
                '}' .
                'for(var i=0;i<ids.length;i++){' .
                    "var id=document.createElement('input');" .
                    "id.type='hidden';id.name='ID[]';id.value=ids[i];form.appendChild(id);" .
                '}' .
                'document.body.appendChild(form);' .
                'form.submit();' .
            '})();';
    }
}
