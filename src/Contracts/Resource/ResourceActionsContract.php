<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use BackedEnum;
use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;

interface ResourceActionsContract
{
    /** @return iterable<ActionContract> */
    public function rowActions(): iterable;

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable;

    /** @return iterable<AsyncAction> */
    public function asyncActions(): iterable;

    /** @return iterable<ToolbarAction|string> */
    public function toolbarActions(): iterable;

    /** @return array<string> */
    public function activeActions(): array;

    public function hasAction(string|BackedEnum $action): bool;

    public function hasAnyAction(string|BackedEnum ...$actions): bool;
}
