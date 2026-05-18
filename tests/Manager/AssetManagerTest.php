<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use MB\Bitrix\AdminKit\Manager\AssetManager;
use PHPUnit\Framework\TestCase;

final class AssetManagerTest extends TestCase
{
    public function testForFormLoadsAdminKitOnly(): void
    {
        $extensions = (new AssetManager())->forForm()->all()['extensions'];

        self::assertContains('mb.admin.kit', $extensions);
        self::assertNotContains('mb.ui.tabs', $extensions);
        self::assertNotContains('mb.ui.dialog-selector', $extensions);
    }

    public function testForGridLoadsAdminKit(): void
    {
        $extensions = (new AssetManager())->forGrid()->all()['extensions'];

        self::assertContains('mb.admin.kit', $extensions);
        self::assertNotContains('mb.ui.tabs', $extensions);
        self::assertNotContains('mb.ui.dialog-selector', $extensions);
    }
}
