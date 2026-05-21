<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Pages;

use Bitrix\Main\Config\Option;
use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;

final class OptionsPageStabilizationTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetOptions();
        $this->setGetRequest();
        unset($GLOBALS['last_redirect']);
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testAjaxPostWithInvalidSessidReturnsJsonErrorAndDoesNotSave(): void
    {
        $this->setAjaxPostRequest([
            'name' => 'Changed',
            'sessid' => 'invalid',
        ]);
        $page = new SessidCapturingOptionsPage();

        ob_start();
        $page->render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('"status":"error"', str_replace(' ', '', $output));
        self::assertTrue(str_contains($output, 'Сессия истекла') || str_contains($output, 'Session expired'));
        self::assertSame('', Option::get('vendor.test', 'name'));
    }

    public function testInvalidSessidShowsAlertOnRegularRender(): void
    {
        $this->setPostRequest(['name' => 'Changed', 'sessid' => 'invalid']);
        $page = new SessidCapturingOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('ui-alert-danger', $html);
        self::assertTrue(str_contains($html, 'Сессия истекла') || str_contains($html, 'Session expired'));
        self::assertSame('', Option::get('vendor.test', 'name'));
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

    public function testEmptyPasswordPreservesStoredOption(): void
    {
        Option::set('vendor.test', 'secret', 'stored-secret');

        $page = new TestableOptionsPage();
        $field = Password::make('Secret', 'secret');
        $page->exposePersist('vendor.test', $field, '');

        self::assertSame('stored-secret', Option::get('vendor.test', 'secret'));
    }

    public function testBelongsToValueIsPersisted(): void
    {
        $page = new TestableOptionsPage();
        $field = BelongsTo::make('User', 'user_id', TestUserTableStub::class)
            ->options(static fn (): array => ['5' => 'Admin']);

        $page->exposePersist('vendor.test', $field, '5');

        self::assertSame('5', Option::get('vendor.test', 'user_id'));
    }

    public function testRememberedTabIsStoredInSessionAndRestored(): void
    {
        $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'] = [];

        $this->setPostRequest(['adminkit_active_tab' => 'advanced']);
        $page = new TabsRememberOptionsPage();
        $page->exposeRememberActiveTabFromRequest();

        self::assertSame('advanced', $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB']['tabs-remember']);

        $tabs = Tabs::make([
            Tab::make('Main', [Text::make('Main', 'main')])->id('main'),
            Tab::make('Advanced', [Text::make('Advanced', 'advanced')])->id('advanced'),
        ])->remember();

        $prepared = $page->exposeApplyRememberedTabs([$tabs])[0];
        self::assertInstanceOf(Tabs::class, $prepared);

        $reflection = new \ReflectionClass($prepared);
        $property = $reflection->getProperty('tabs');
        $property->setAccessible(true);
        /** @var array<int, Tab> $tabItems */
        $tabItems = $property->getValue($prepared);

        $activeIds = [];
        foreach ($tabItems as $tab) {
            if ($tab->isActive()) {
                $activeIds[] = $tab->getId();
            }
        }

        self::assertSame(['advanced'], $activeIds);
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

    public function testFlatFieldRendersStoredValue(): void
    {
        Option::set('vendor.test', 'name', 'stored-flat');

        $page = new FlatOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('value="stored-flat"', $html);
        self::assertStringNotContainsString('name="adminkit_ajax"', $html);
    }

    public function testFlatFieldPersistsPostedValueOnRegularPost(): void
    {
        $this->setPostRequest(['name' => 'posted-flat']);
        $page = new FlatOptionsPage();

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('posted-flat', Option::get('vendor.test', 'name'));
    }

    public function testAjaxPostPersistsAndReturnsJson(): void
    {
        $this->setAjaxPostRequest(['name' => 'posted-ajax']);
        $page = new FlatOptionsPage();

        ob_start();
        $page->render();
        $output = (string)ob_get_clean();

        self::assertSame('posted-ajax', Option::get('vendor.test', 'name'));
        self::assertStringContainsString('"status":"success"', str_replace(' ', '', $output));
    }

    public function testTabsFieldRendersStoredValueInServerHtml(): void
    {
        Option::set('vendor.test', 'tab_name', 'stored-tab');

        $page = new TabbedOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('data-adminkit-tabs-prerendered="Y"', $html);
        self::assertStringContainsString('ui-tabs__tab-body_data', $html);
        self::assertStringContainsString('value="stored-tab"', $html);
        self::assertStringContainsString('name="tab_name"', $html);
    }

    public function testTabsFieldPersistsOnRegularPost(): void
    {
        $this->setPostRequest(['tab_name' => 'posted-tab']);
        $page = new TabbedOptionsPage();

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('posted-tab', Option::get('vendor.test', 'tab_name'));
    }

    public function testCollectEditableFieldsExtractsFromTabsAndNestedBox(): void
    {
        $page = new NestedLayoutOptionsPage();
        $columns = array_map(static fn (FieldContract $field): string => $field->getColumn(), $page->exposeCollectEditableFields());

        self::assertContains('flat', $columns);
        self::assertContains('in_tab', $columns);
        self::assertContains('in_box', $columns);
        self::assertNotContains('readonly_field', $columns);
    }

    public function testReadonlyFieldIsNotPersistedButEditableFieldIs(): void
    {
        Option::set('vendor.test', 'editable_field', 'old-editable');
        Option::set('vendor.test', 'readonly_field', 'keep-readonly');

        $this->setPostRequest([
            'editable_field' => 'new-editable',
            'readonly_field' => 'hacked',
        ]);
        $page = new ReadonlyOptionsPage();

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('new-editable', Option::get('vendor.test', 'editable_field'));
        self::assertSame('keep-readonly', Option::get('vendor.test', 'readonly_field'));
    }

    public function testMultisiteSaveAndLoad(): void
    {
        $this->setPostRequest([
            'name' => 'Name for S2',
            'site_id' => 's2',
        ]);
        $page = new MultisiteTestOptionsPage();

        ob_start();
        $page->render();
        ob_end_clean();

        self::assertSame('Name for S2', Option::get('vendor.test', 'name', '', 's2'));
        self::assertSame('', Option::get('vendor.test', 'name', '', 's1'));

        // Load wrapper for s2
        $components = iterator_to_array($page->components());
        $wrapper = $page->buildOptionsWrapper('vendor.test', 's2', $components);
        self::assertSame('Name for S2', $wrapper->get('name'));
    }

    public function testFieldsBackwardPath(): void
    {
        $page = new FieldsBackwardPathOptionsPage();
        $components = iterator_to_array($page->components());

        self::assertCount(1, $components);
        self::assertSame('name', $components[0]->getColumn());
    }

    public function testHiddenActiveTabRenders(): void
    {
        $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'] = ['tabbed-options' => 'main'];
        $page = new TabbedOptionsPage();

        ob_start();
        $page->render();
        $html = (string)ob_get_clean();

        self::assertStringContainsString('name="adminkit_active_tab" value="main"', $html);
    }

    public function testReactivePost(): void
    {
        $this->setPostRequest([
            'adminkit_action' => 'reactive',
            'name' => 'Reactive Name',
        ]);
        $page = new FlatOptionsPage();

        ob_start();
        try {
            $page->render();
        } catch (\Throwable) {
            // handleReactive ends with terminate()
        }
        $json = (string)ob_get_clean();
        preg_match('/\{.*\}/s', $json, $matches);
        $payload = json_decode($matches[0] ?? '', true);

        self::assertSame('success', $payload['status']);
    }

    public function testFieldsInsideContainers(): void
    {
        $page = new NestedLayoutOptionsPage();
        $fields = $page->collectEditableFields();
        self::assertCount(3, $fields); // flat, in_tab, in_box (readonly is filtered out of collectEditableFields/collectAllFields)
    }
}

final class SessidCapturingOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'sessid-options';
    }

    public static function getTitle(): string
    {
        return 'Sessid options';
    }

    public function components(): iterable
    {
        return [Text::make('Name', 'name')];
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

    public function components(): iterable
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
     * @param array<int,FieldContract|\MB\Bitrix\AdminKit\Contracts\UI\ComponentContract|Tab> $components
     */
    public function exposeBuildWrapper(string $moduleId, string $siteId, array $components): DataWrapper
    {
        return $this->buildOptionsWrapper($moduleId, $siteId, $components);
    }

    public function exposeCheckVisibilityRule(array $rule, mixed $currentValue): bool
    {
        return $this->checkVisibilityRule($rule, $currentValue);
    }

    /** @return list<FieldContract> */
    public function exposeCollectEditableFields(): array
    {
        return $this->collectEditableFields();
    }
}

final class FlatOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'flat-options';
    }

    public static function getTitle(): string
    {
        return 'Flat options';
    }

    public function components(): iterable
    {
        return [
            Text::make('Name', 'name'),
        ];
    }
}

final class TabbedOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'tabbed-options';
    }

    public static function getTitle(): string
    {
        return 'Tabbed options';
    }

    public function components(): iterable
    {
        return [
            Tabs::make([
                Tab::make('Main', [
                    Text::make('Tab name', 'tab_name'),
                ])->active()->id('main'),
            ]),
        ];
    }
}

final class NestedLayoutOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'nested-layout-options';
    }

    public static function getTitle(): string
    {
        return 'Nested layout options';
    }

    public function components(): iterable
    {
        return [
            Text::make('Flat', 'flat'),
            Tabs::make([
                Tab::make('Tab', [
                    Text::make('In tab', 'in_tab'),
                    Box::make('Box', [
                        Text::make('In box', 'in_box'),
                    ]),
                ])->id('tab'),
            ]),
            Text::make('Readonly', 'readonly_field')->readonly(),
        ];
    }

    /** @return list<FieldContract> */
    public function exposeCollectEditableFields(): array
    {
        return $this->collectEditableFields();
    }
}

final class ReadonlyOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'readonly-options';
    }

    public static function getTitle(): string
    {
        return 'Readonly options';
    }

    public function components(): iterable
    {
        return [
            Text::make('Editable', 'editable_field'),
            Text::make('Readonly', 'readonly_field')->readonly(),
        ];
    }
}

final class TabsRememberOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'tabs-remember';
    }

    public static function getTitle(): string
    {
        return 'Tabs remember';
    }

    public function exposeRememberActiveTabFromRequest(): void
    {
        $this->rememberActiveTabFromRequest();
    }

    /**
     * @param array<int,mixed> $components
     * @return array<int,mixed>
     */
    public function exposeApplyRememberedTabs(array $components): array
    {
        return $this->applyRememberedTabs($components);
    }
}

final class TestUserTableStub
{
    public static function getList(array $params = []): object
    {
        return new class () {
            public function fetch(): ?array
            {
                return null;
            }
        };
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

    public function components(): iterable
    {
        return [
            Tab::make('Orphan', [
                Text::make('Orphan option', 'orphan'),
            ]),
            Text::make('Visible option', 'visible'),
        ];
    }
}

final class MultisiteTestOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';
    protected bool $multiSite = true;

    public static function getId(): string
    {
        return 'multisite-test';
    }

    public static function getTitle(): string
    {
        return 'Multisite Test';
    }

    public function components(): iterable
    {
        return [
            Text::make('Name', 'name'),
        ];
    }
}

final class FieldsBackwardPathOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.test';

    public static function getId(): string
    {
        return 'fields-backward';
    }

    public static function getTitle(): string
    {
        return 'Fields Backward';
    }

    public function fields(): iterable
    {
        return [
            Text::make('Name', 'name'),
        ];
    }
}
