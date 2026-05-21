<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class FormPageJsExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $_POST = [];
        $GLOBALS['APPLICATION'] = new class () {
            public function SetTitle(string $title): void
            {
            }

            public function IncludeComponent(string $name, string $template, array $params): void
            {
            }
        };
    }

    public function testFormPageRenderInitializesAdminKitFormScript(): void
    {
        $page = new FormPage(new ProductResource());

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('var m="Form"', $html);
        self::assertStringContainsString('BX.Runtime.loadExtension("mb.admin.kit")', $html);
        self::assertStringContainsString('MB.AdminKit[m].init', $html);
        self::assertStringNotContainsString("data.set('adminkit_async_save', 'Y')", $html);
    }

    public function testFormPageRenderInitializesVisibilityWithRuleJson(): void
    {
        $page = new class (new ProductResource()) extends FormPage {
            public function fields(): iterable
            {
                return [
                    Text::make('Type', 'TYPE'),
                    Text::make('Name', 'NAME')->visibleWhen('TYPE', '!=', 'hidden'),
                ];
            }
        };

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('var m="Visibility"', $html);
        self::assertStringContainsString('&quot;operator&quot;:&quot;!=&quot;', $html);
        self::assertStringNotContainsString('function matchesRule(rule, val)', $html);
    }
}
