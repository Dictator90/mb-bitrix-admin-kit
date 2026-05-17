<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Js;

use PHPUnit\Framework\TestCase;

final class AdminKitExtensionConfigTest extends TestCase
{
    public function testMbAdminKitExtensionIncludesTabsAndDialogSelectorBundles(): void
    {
        $root = dirname(__DIR__, 2) . '/install/js/mb/admin/kit';
        /** @var array{js: list<string>, css: list<string>} $config */
        $config = require $root . '/config.php';

        self::assertContains('dist/tabs.bundle.js', $config['js']);
        self::assertContains('dist/dialog-selector.bundle.js', $config['js']);
        self::assertContains('dist/tabs.bundle.css', $config['css']);

        foreach ($config['js'] as $script) {
            self::assertFileExists($root . '/' . $script, 'Missing JS asset: ' . $script);
        }

        foreach ($config['css'] as $stylesheet) {
            self::assertFileExists($root . '/' . $stylesheet, 'Missing CSS asset: ' . $stylesheet);
        }

        self::assertContains('main.core.collections', $config['rel'] ?? []);
        self::assertContains('main.core.events', $config['rel'] ?? []);
        self::assertFalse($config['skip_core'] ?? true);
    }
}
