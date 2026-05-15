<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Pages;

use Bitrix\Main\Config\Option;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Pages\OptionsPage;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use PHPUnit\Framework\TestCase;

final class OptionsPageStabilizationTest extends TestCase
{
    protected function setUp(): void
    {
        Option::reset();
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_GET'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_HEADERS'] = [];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_REQUEST_URI'] = '/bitrix/admin/options.php';
        $_POST = [];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = '';
    }

    protected function tearDown(): void
    {
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = false;
    }

    public function testAjaxPostWithInvalidSessidReturnsJsonErrorAndDoesNotSave(): void
    {
        $page = new SessidCapturingOptionsPage();
        SessidCapturingOptionsPage::$jsonPayload = null;

        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = [
            'adminkit_ajax' => 'Y',
            'name' => 'Changed',
        ];

        try {
            $page->render();
            self::fail('Expected JSON exit');
        } catch (\RuntimeException $exception) {
            self::assertSame('json_exit', $exception->getMessage());
        }

        self::assertIsArray(SessidCapturingOptionsPage::$jsonPayload);
        self::assertSame('error', SessidCapturingOptionsPage::$jsonPayload['status'] ?? null);
        self::assertStringContainsString('Сессия истекла', (string)(SessidCapturingOptionsPage::$jsonPayload['message'] ?? ''));
        self::assertSame(0, Option::$setCalls);
    }

    public function testInvalidSessidShowsAlertOnRegularRender(): void
    {
        $page = new SessidCapturingOptionsPage();

        $GLOBALS['MB_ADMIN_KIT_TEST_IS_POST'] = true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;
        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['name' => 'Changed'];

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('ui-alert-danger', $html);
        self::assertStringContainsString('Сессия истекла', $html);
        self::assertSame(0, Option::$setCalls);
    }

    public function testArrayValueIsStoredAsJsonAndReadBackAsArray(): void
    {
        $page = new TestableOptionsPage();
        $field = Text::make('Tags', 'tags');

        $page->exposePersist('vendor.test', $field, ['a', 'b']);

        self::assertSame('["a","b"]', Option::get('vendor.test', 'tags'));

        $wrapper = $page->exposeBuildWrapper('vendor.test', '', [Text::make('Tags', 'tags')]);
        self::assertSame(['a', 'b'], $wrapper->get('tags'));
    }

    public function testScalarValueIsStoredAsString(): void
    {
        $page = new TestableOptionsPage();
        $field = Text::make('Name', 'name');

        $page->exposePersist('vendor.test', $field, 'hello');

        self::assertSame('hello', Option::get('vendor.test', 'name'));
        self::assertSame('hello', $page->exposeUnserialize($field, 'hello'));
    }

    public function testEmptyValueDeletesOption(): void
    {
        Option::set('vendor.test', 'name', 'old');

        $page = new TestableOptionsPage();
        $field = Text::make('Name', 'name');
        $page->exposePersist('vendor.test', $field, '');

        self::assertNull(Option::get('vendor.test', 'name', null));
    }

    public function testCheckVisibilityRuleSupportsNotEqualsAndInOperators(): void
    {
        $page = new TestableOptionsPage();

        self::assertFalse($page->exposeCheckVisibilityRule([
            'column' => 'type',
            'operator' => '!=',
            'value' => 'Y',
        ], 'Y'));

        self::assertTrue($page->exposeCheckVisibilityRule([
            'column' => 'type',
            'operator' => 'in',
            'value' => ['a', 'b'],
        ], 'b'));

        self::assertFalse($page->exposeCheckVisibilityRule([
            'column' => 'type',
            'operator' => 'not in',
            'value' => ['a', 'b'],
        ], 'b'));
    }

    public function testRenderedVisibilityScriptSupportsOperators(): void
    {
        $page = new TestableOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('var m="Visibility"', $html);
        self::assertStringContainsString('var m="OptionsPage"', $html);
        self::assertStringContainsString('&quot;operator&quot;:&quot;!=&quot;', $html);
        self::assertStringNotContainsString('function matchesRule(rule, val)', $html);
        self::assertStringNotContainsString("headers: { 'X-Requested-With': 'XMLHttpRequest' }", $html);
    }

    public function testOrphanTabDoesNotBreakRender(): void
    {
        $page = new SingleTabOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('Visible option', $html);
        self::assertStringNotContainsString('Orphan option', $html);
    }
}

final class SessidCapturingOptionsPage extends OptionsPage
{
    /** @var array<string,mixed>|null */
    public static ?array $jsonPayload = null;

    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'sessid-options';
    }

    public static function getTitle(): string
    {
        return 'Sessid options';
    }

    protected function components(): iterable
    {
        return [Text::make('Name', 'name')];
    }

    protected function sendJsonAndExit(array $payload): never
    {
        self::$jsonPayload = $payload;

        throw new \RuntimeException('json_exit');
    }
}

final class TestableOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'testable-options';
    }

    public static function getTitle(): string
    {
        return 'Testable options';
    }

    protected function components(): iterable
    {
        return [
            Text::make('Type', 'type'),
            Text::make('Name', 'name')->visibleWhen('type', '!=', 'hidden'),
        ];
    }

    public function exposePersist(string $moduleId, FieldContract $field, mixed $value, string $siteId = ''): void
    {
        $this->persistOptionValue($moduleId, $field, $value, $siteId);
    }

    public function exposeUnserialize(FieldContract $field, string $value): mixed
    {
        return $this->unserializeOptionValue($field, $value);
    }

    /**
     * @param array<int,FieldContract|\MB\Bitrix\AdminKit\Contracts\ComponentContract|Tab> $components
     */
    public function exposeBuildWrapper(string $moduleId, string $siteId, array $components): DataWrapper
    {
        return $this->buildOptionsWrapper($moduleId, $siteId, $components);
    }

    public function exposeCheckVisibilityRule(array $rule, mixed $currentValue): bool
    {
        return $this->checkVisibilityRule($rule, $currentValue);
    }
}

final class SingleTabOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'single-tab-options';
    }

    public static function getTitle(): string
    {
        return 'Single tab options';
    }

    protected function components(): iterable
    {
        return [
            Tab::make('Orphan', [
                Text::make('Orphan option', 'orphan'),
            ]),
            Text::make('Visible option', 'visible'),
        ];
    }
}
