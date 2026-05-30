<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use CUtil;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class RowAction implements ActionContract
{
    protected string $id;
    protected string $label;
    protected ?string $url = null;
    protected string $type = 'default';
    protected bool $useConfirm = false;
    protected ?string $confirmText = null;
    protected bool $useSidePanel = true;

    public function __construct(string $id, string $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public static function edit(?string $url = null): static
    {
        $action = new static('edit', LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_ROW_ACTION_EDIT', 'Edit'));
        $action->url = $url;
        $action->useSidePanel = true;

        return $action;
    }

    public static function view(?string $url = null): static
    {
        $action = new static('view', LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_ROW_ACTION_VIEW', 'View'));
        $action->url = $url;
        $action->useSidePanel = true;

        return $action;
    }

    public static function delete(): static
    {
        $action = new static('delete', LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_ROW_ACTION_DELETE', 'Delete'));
        $action->useConfirm = true;
        $action->confirmText = LocalizedMessage::get(
            __FILE__,
            'MB_ADMIN_KIT_ROW_ACTION_DELETE_CONFIRM',
            'Are you sure you want to delete this item?',
        );
        $action->type = 'delete';

        return $action;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isUseConfirm(): bool
    {
        return $this->useConfirm;
    }

    public function getConfirmText(): ?string
    {
        return $this->confirmText;
    }

    public function isUseSidePanel(): bool
    {
        return $this->useSidePanel;
    }

    public function toArray(array $row, string $baseUrl = '', ?string $gridId = null, ?int $sidePanelWidth = null): array
    {
        $id = $row['ID'] ?? $row['id'] ?? '';
        $sep = str_contains($baseUrl, '?') ? '&' : '?';

        $result = [
            'text' => $this->label,
            'default' => $this->id === 'edit',
        ];

        if ($this->type === 'delete') {
            $deleteUrl = $baseUrl . $sep . 'action=delete&id=' . (int)$id
                . '&sessid=' . bitrix_sessid();
            $confirmText = CUtil::JSEscape($this->confirmText ?? '');
            $result['onclick'] = "if(confirm('" . $confirmText . "')) { window.location.href='" . CUtil::JSEscape(
                $deleteUrl
            ) . "'; }";
        } else {
            $url = $this->url ?: ($baseUrl . $sep . 'action=' . $this->id . '&id=#ID#');
            $url = str_replace('#ID#', (string)$id, $url);

            if ($this->useSidePanel) {
                $result['onclick'] = $this->buildSidePanelOpenJs($url, $gridId, $sidePanelWidth);
            } else {
                $result['href'] = $url;
            }
        }

        return $result;
    }

    protected function buildSidePanelOpenJs(string $url, ?string $gridId = null, ?int $width = null): string
    {
        $urlJs = CUtil::JSEscape($url);
        $width = (int)($width ?? 1100);

        if ($gridId === null || $gridId === '') {
            return "BX.SidePanel.Instance.open('{$urlJs}',{width:{$width},cacheable:false,allowChangeHistory:false})";
        }

        $gridIdJson = json_encode($gridId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '""';

        return
            "BX.SidePanel.Instance.open('{$urlJs}',{" .
                "width:{$width}," .
                'cacheable:false,' .
                'allowChangeHistory:false,' .
                'events:{' .
                    'onCloseComplete:function(){' .
                        'var manager=BX.Main&&BX.Main.gridManager?BX.Main.gridManager:null;' .
                        'if(!manager){return;}' .
                        "var grid=manager.getInstanceById?manager.getInstanceById({$gridIdJson}):null;" .
                        'if(!grid&&manager.getById){' .
                            "var pair=manager.getById({$gridIdJson});" .
                            'grid=pair&&(pair.instance||pair.grid)?(pair.instance||pair.grid):null;' .
                        '}' .
                        "if(grid&&typeof grid.reload==='function'){grid.reload();}" .
                    '}' .
                '}' .
            '})';
    }

}
