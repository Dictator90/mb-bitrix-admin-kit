<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use BackedEnum;

trait HasResourceActions
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function rowActions(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function bulkActions(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Action\AsyncAction> */
    public function asyncActions(): iterable
    {
        return [];
    }

    /**
     * Кастомные кнопки тулбара. Экспорт сюда добавлять не нужно — он управляется
     * единым флагом {@see \MB\Bitrix\AdminKit\Contracts\Resource\ResourceExportContract::exportEnabled()}.
     *
     * @return iterable<\MB\Bitrix\AdminKit\Manager\ToolbarAction|string>
     */
    public function toolbarActions(): iterable
    {
        return [];
    }

    /** Кастомная подпись кнопки создания в тулбаре (null — стандартная "Create"). */
    public function createButtonLabel(): ?string
    {
        return null;
    }

    /** Показывать стандартную кнопку "Создать" в тулбаре (false — резурс рисует свою через toolbarActions()). */
    public function showCreateButton(): bool
    {
        return true;
    }

    /** @return array<string> */
    public function activeActions(): array
    {
        return ['create', 'view', 'update', 'delete', 'export'];
    }

    public function hasAction(string|BackedEnum $action): bool
    {
        $actionId = $action instanceof BackedEnum ? (string)$action->value : $action;

        return in_array($actionId, $this->activeActions(), true);
    }

    public function hasAnyAction(string|BackedEnum ...$actions): bool
    {
        foreach ($actions as $action) {
            if ($this->hasAction($action)) {
                return true;
            }
        }

        return false;
    }
}
