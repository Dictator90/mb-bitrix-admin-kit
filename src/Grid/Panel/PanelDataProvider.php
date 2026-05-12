<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Panel;

/**
 * @deprecated Not used with the new Grid API. Panel is built via Grid::buildActionPanel().
 */
class PanelDataProvider
{
    public function __construct(
        protected string $gridId,
        protected array $actions = [],
    ) {}

    public function getActions(): array
    {
        return $this->actions;
    }
}
