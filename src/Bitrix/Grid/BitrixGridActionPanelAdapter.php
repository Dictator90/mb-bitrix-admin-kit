<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Grid;

use Bitrix\Main\Grid\Panel\Snippet as GridPanelSnippet;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Grid\Grid;

final class BitrixGridActionPanelAdapter
{
    /** @return array<string,mixed> */
    public function componentParams(Grid $grid): array
    {
        $items = [];

        if ($grid->hasEditableFields()) {
            $items[] = $this->buildInlineEditButton();
        }

        foreach ($grid->getBulkActions() as $action) {
            $items[] = $this->buildBulkActionButton($grid, $action);
        }

        return ['GROUPS' => [['ITEMS' => $items]]];
    }

    /** @return array<string,mixed> */
    public function buildInlineEditButton(): array
    {
        return (new GridPanelSnippet())->getEditButton();
    }

    /** @return array<string,mixed> */
    private function buildBulkActionButton(Grid $grid, BulkAction $action): array
    {
        $item = [
            'TYPE' => 'BUTTON',
            'ID' => $action->getId(),
            'TEXT' => $action->getLabel(),
            'ONCHANGE' => [[
                'ACTION' => 'CALLBACK',
                'DATA' => [[
                    'JS' => $this->buildBulkActionCallbackJs($grid, $action->getId()),
                ]],
            ]],
        ];

        if ($action->needsConfirm()) {
            $item['ONCHANGE'][0]['CONFIRM'] = true;
            $item['ONCHANGE'][0]['CONFIRM_MESSAGE'] = $action->getConfirmText() ?? 'Are you sure?';
        }

        if ($action->isDanger()) {
            $item['CLASS'] = 'adm-btn-danger';
        }

        return $item;
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
