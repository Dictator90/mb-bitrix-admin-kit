<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use MB\Bitrix\AdminKit\Contracts\ActionContract;

class BulkAction implements ActionContract
{
    protected string $id;
    protected string $label;
    protected bool $needsConfirm = false;
    protected ?string $confirmText = null;

    public function __construct(string $id, string $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public static function delete(): static
    {
        $action = new static('delete', 'Удалить выбранные');
        $action->needsConfirm = true;
        $action->confirmText = 'Вы уверены, что хотите удалить выбранные записи?';

        return $action;
    }

    public static function make(string $id, string $label): static
    {
        return new static($id, $label);
    }

    public function confirm(string $text): static
    {
        $this->needsConfirm = true;
        $this->confirmText = $text;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isDelete(): bool
    {
        return $this->id === 'delete';
    }

    public function needsConfirm(): bool
    {
        return $this->needsConfirm;
    }

    public function getConfirmText(): ?string
    {
        return $this->confirmText;
    }
}
