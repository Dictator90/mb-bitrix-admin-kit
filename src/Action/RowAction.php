<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use MB\Bitrix\AdminKit\Contracts\ActionContract;

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
        $action = new static('edit', 'Редактировать');
        $action->url = $url;
        $action->useSidePanel = true;

        return $action;
    }

    public static function view(?string $url = null): static
    {
        $action = new static('view', 'Просмотр');
        $action->url = $url;
        $action->useSidePanel = true;

        return $action;
    }

    public static function delete(): static
    {
        $action = new static('delete', 'Удалить');
        $action->useConfirm = true;
        $action->confirmText = 'Вы уверены, что хотите удалить этот элемент?';
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

    public function toArray(array $row, string $baseUrl = ''): array
    {
        $id  = $row['ID'] ?? $row['id'] ?? '';
        $sep = str_contains($baseUrl, '?') ? '&' : '?';

        $result = [
            'text'    => $this->label,
            'default' => $this->id === 'edit',
        ];

        if ($this->type === 'delete') {
            $deleteUrl   = $baseUrl . $sep . 'action=delete&id=' . (int)$id
                . '&sessid=' . bitrix_sessid();
            $confirmText = \CUtil::JSEscape($this->confirmText ?? '');
            $result['onclick'] = "if(confirm('" . $confirmText . "')) { window.location.href='" . \CUtil::JSEscape($deleteUrl) . "'; }";
        } else {
            $url = $this->url ?: ($baseUrl . $sep . 'action=' . $this->id . '&id=#ID#');
            $url = str_replace('#ID#', (string)$id, $url);

            if ($this->useSidePanel) {
                $result['onclick'] = "BX.SidePanel.Instance.open('" . \CUtil::JSEscape($url) . "')";
            } else {
                $result['href'] = $url;
            }
        }

        return $result;
    }
}
