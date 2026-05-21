<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Integration;

use Bitrix\Main\Config\Option;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\Handlers\OptionsPagePostHandler;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use PHPUnit\Framework\TestCase;

final class OptionPersistenceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear options for test module before each test
        Option::delete('test_module');
    }

    protected function tearDown(): void
    {
        Option::delete('test_module');
        parent::tearDown();
    }

    public function testSetAndGetOptionValue(): void
    {
        Option::set('test_module', 'test_key', 'test_value');
        $this->assertSame('test_value', Option::get('test_module', 'test_key'));
    }

    public function testDeleteOptionValue(): void
    {
        Option::set('test_module', 'test_key', 'test_value');
        Option::delete('test_module', ['name' => 'test_key']);
        $this->assertSame('', Option::get('test_module', 'test_key'));
    }

    public function testOptionsPagePostHandlerWithRealOption(): void
    {
        $field = Text::make('Test Field', 'test_key');

        $page = new class ($field) extends OptionsPage {
            private $field;

            public function __construct($field)
            {
                $this->field = $field;
                parent::__construct();
            }

            public static function getId(): string
            {
                return 'test_page';
            }

            public static function getTitle(): string
            {
                return 'Test Page';
            }

            public function components(): iterable
            {
                return [$this->field];
            }
        };

        // Reflect and set moduleId to test_module
        $ref = new \ReflectionClass($page);
        $prop = $ref->getProperty('moduleId');
        $prop->setAccessible(true);
        $prop->setValue($page, 'test_module');

        $handler = new OptionsPagePostHandler();

        // Assert option is empty initially
        $this->assertSame('', Option::get('test_module', 'test_key'));

        // Persist a value
        $handler->persistOptionValue($page, 'test_module', $field, 'hello-world', '');

        // Verify it was persisted to SQLite
        $this->assertSame('hello-world', Option::get('test_module', 'test_key'));

        // Persist empty value to trigger delete/default behavior
        $handler->persistOptionValue($page, 'test_module', $field, '', '');
        $this->assertSame('', Option::get('test_module', 'test_key'));
    }

    public function testSiteDependentOptionValue(): void
    {
        Option::set('test_module', 'test_key', 'site_value', 's1');
        $this->assertSame('site_value', Option::get('test_module', 'test_key', '', 's1'));

        Option::delete('test_module', ['name' => 'test_key', 'site_id' => 's1']);
        $this->assertSame('', Option::get('test_module', 'test_key', '', 's1'));
    }
}
