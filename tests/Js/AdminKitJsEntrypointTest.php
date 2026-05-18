<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Js;

use PHPUnit\Framework\TestCase;

final class AdminKitJsEntrypointTest extends TestCase
{
    public function testCoreIndexExportsTabsAndDialogSelector(): void
    {
        $source = (string)file_get_contents(dirname(__DIR__, 2) . '/install/js/mb/admin/kit/src/index.js');

        self::assertStringContainsString('./tabs/index', $source);
        self::assertStringContainsString('./dialog-selector/index', $source);
        self::assertStringContainsString('Tabs', $source);
        self::assertStringContainsString('DialogSelector', $source);
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
