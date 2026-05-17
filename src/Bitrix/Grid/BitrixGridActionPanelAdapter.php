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

        // Pre-fill default group items
        $defaultItems = [];
        if ($grid->hasEditableFields()) {
            $defaultItems[] = (new GridPanelSnippet())->getEditButton();
        }

        if ($grid->shouldShowSelectAllRecordsCheckbox()) {
            $defaultItems[] = (new GridPanelSnippet())->getForAllCheckbox();
        }

        $allPanelItems = $grid->getBulkActions();
        $this->validateUniqueActionIds($grid);

        foreach ($allPanelItems as $item) {
            $groupName = $item->getGroup();
            $actionsByGroup[$groupName][] = $item;
        }

        // Add default group actions to default items
        if (isset($actionsByGroup['default'])) {
            usort($actionsByGroup['default'], static fn (BulkPanelItemContract $a, BulkPanelItemContract $b): int => $a->getSort() <=> $b->getSort());
            foreach ($actionsByGroup['default'] as $item) {
                $defaultItems[] = $this->buildPanelItem($grid, $item);
            }
            unset($actionsByGroup['default']);
        }

        if ($defaultItems !== []) {
            $groups[] = [
                'ITEMS' => $defaultItems,
            ];
        }

        // Sort remaining groups by key for consistency
        ksort($actionsByGroup);

        foreach ($actionsByGroup as $groupName => $items) {
            usort($items, static fn (BulkPanelItemContract $a, BulkPanelItemContract $b): int => $a->getSort() <=> $b->getSort());

            $panelItems = [];
            foreach ($items as $item) {
                $panelItems[] = $this->buildPanelItem($grid, $item);
            }

            if ($panelItems !== []) {
                $groups[] = ['ITEMS' => $panelItems];
            }
        }

        return $groups;
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
        $items = [];
        foreach ($dropdown->getItems() as $action) {
            $items[] = $this->buildDropdownItem($grid, $action);
        }

        return array_filter([
            'TYPE' => Types::DROPDOWN,
            'ID' => $dropdown->getId(),
            'NAME' => strtoupper($dropdown->getId()),
            'TEXT' => $dropdown->getLabel(),
            'TITLE' => $dropdown->getLabel(),
            'MULTIPLE' => $dropdown->isMultiple() ? 'Y' : 'N',
            'ITEMS' => $items,
        ]);
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
                "}" .
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
