<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Panel;

/**
 * @deprecated Not used with the new Grid API. Bulk delete is handled via POST in IndexPage.
 */
class BulkDeletePanelAction
{
    public function __construct(private \Closure $deleteCallback) {}

    public function execute(array $ids): void
    {
        if (!empty($ids)) {
            ($this->deleteCallback)($ids);
        }
    }
}
