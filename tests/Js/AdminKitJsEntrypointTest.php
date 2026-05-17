<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Js;

use PHPUnit\Framework\TestCase;

final class AdminKitJsEntrypointTest extends TestCase
{
    public function testCoreIndexHasNoTabsOrDialogSelector(): void
    {
        $source = (string)file_get_contents(dirname(__DIR__, 2) . '/install/js/mb/admin/kit/src/index.js');

        self::assertStringNotContainsString('./tabs', $source);
        self::assertStringNotContainsString('./dialog-selector', $source);
        self::assertStringNotContainsString('initTabs', $source);
        self::assertStringNotContainsString('initAll', $source);
    }

    public function testTabsAndDialogSelectorSourcesExportClassesOnly(): void
    {
        $tabsIndex = (string)file_get_contents(dirname(__DIR__, 2) . '/install/js/mb/admin/kit/src/tabs/index.js');
        $dialogIndex = (string)file_get_contents(dirname(__DIR__, 2) . '/install/js/mb/admin/kit/src/dialog-selector/index.js');

        self::assertStringContainsString('export', $tabsIndex);
        self::assertStringNotContainsString('initializer', $tabsIndex);
        self::assertStringNotContainsString('initTabs', $tabsIndex);

        self::assertStringContainsString('DialogSelector', $dialogIndex);
        self::assertStringNotContainsString('initializer', $dialogIndex);
        self::assertStringNotContainsString('initDialogSelector', $dialogIndex);
    }
}
