<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Support\AdminKitJs;
use PHPUnit\Framework\TestCase;

final class IndexPageGridCollapsibleJsTest extends TestCase
{
    public function testRenderInitUsesGridCollapsibleModule(): void
    {
        ob_start();
        AdminKitJs::renderInit('GridCollapsible', []);
        $html = (string)ob_get_clean();

        self::assertStringContainsString('var m="GridCollapsible"', $html);
        self::assertStringContainsString('BX.Runtime.loadExtension("mb.admin.kit")', $html);
        self::assertStringContainsString('MB.AdminKit[m].init', $html);
    }

    public function testDeprecatedInlineRendererDelegatesToRenderInit(): void
    {
        ob_start();
        AdminKitJs::renderGridCollapsibleInitialState();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('var m="GridCollapsible"', $html);
        self::assertStringNotContainsString('patchCustom', $html);
    }
}
