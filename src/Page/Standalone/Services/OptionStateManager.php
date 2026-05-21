<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone\Services;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;

final class OptionStateManager
{
    public function __construct(private readonly string $sessionKey)
    {
    }

    public function rememberActiveTab(string $tabId): void
    {
        if ($tabId === '') {
            return;
        }

        if (!isset($_SESSION['MB_ADMIN_KIT_ACTIVE_TAB']) || !is_array($_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'])) {
            $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'] = [];
        }

        $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'][$this->sessionKey] = $tabId;
    }

    public function getActiveTabId(): ?string
    {
        $stored = $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'][$this->sessionKey] ?? null;

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    /**
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     * @return array<int, FieldContract|ComponentContract|Tab>
     */
    public function applyActiveTabs(array $components): array
    {
        $storedTabId = $this->getActiveTabId();
        $result = [];

        foreach ($components as $item) {
            if ($item instanceof Tabs && $item->remembersActiveTab()) {
                $result[] = $item->withRememberedActiveTab($storedTabId);
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}
