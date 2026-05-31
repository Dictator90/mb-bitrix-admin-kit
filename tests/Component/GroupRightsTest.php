<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Component;

use MB\Bitrix\AdminKit\Component\GroupRights;
use MB\Bitrix\AdminKit\Contracts\UI\LayoutComponentContract;
use MB\Bitrix\AdminKit\Exceptions\AdminKitException;
use PHPUnit\Framework\TestCase;

final class GroupRightsTest extends TestCase
{
    public function testImplementsLayoutComponentContract(): void
    {
        self::assertInstanceOf(LayoutComponentContract::class, GroupRights::make('my.module'));
    }

    public function testExtractFieldsReturnsEmpty(): void
    {
        self::assertSame([], GroupRights::make('my.module')->extractFields());
    }

    public function testThrowsWhenModuleIdIsMissingAndConstantUndefined(): void
    {
        if (defined('ADMIN_MODULE_NAME')) {
            self::markTestSkipped('ADMIN_MODULE_NAME is already defined in this test process.');
        }

        $this->expectException(AdminKitException::class);

        $component = new class () extends GroupRights {
            protected function renderBitrixRightsTable(string $moduleId): string
            {
                return '';
            }
        };

        $component->render();
    }

    public function testRenderEmitsUpdateMarkerByDefault(): void
    {
        $component = new class ('my.module') extends GroupRights {
            protected function renderBitrixRightsTable(string $moduleId): string
            {
                return '<tr data-module="' . $moduleId . '"><td>row</td></tr>';
            }
        };

        $html = $component->render();

        self::assertStringContainsString('name="Update"', $html);
        self::assertStringContainsString('value="Y"', $html);
        self::assertStringContainsString('data-module="my.module"', $html);
        self::assertStringContainsString('adminkit-bx-rights__table', $html);
    }

    public function testWithoutSaveTriggerOmitsUpdateMarker(): void
    {
        $component = new class ('my.module') extends GroupRights {
            protected function renderBitrixRightsTable(string $moduleId): string
            {
                return '<tr><td>row</td></tr>';
            }
        };

        $html = $component->withoutSaveTrigger()->render();

        self::assertStringNotContainsString('name="Update"', $html);
    }

    public function testHandleFormPostIsNoopWithoutUpdateMarker(): void
    {
        $component = new class ('my.module') extends GroupRights {
            public int $invocations = 0;

            protected function renderBitrixRightsTable(string $moduleId): string
            {
                $this->invocations++;

                return '';
            }
        };

        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $previousUpdate = $_REQUEST['Update'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_REQUEST['Update']);

        try {
            $component->handleFormPost();
        } finally {
            self::restoreServer('REQUEST_METHOD', $previousMethod);
            self::restoreRequest('Update', $previousUpdate);
        }

        self::assertSame(0, $component->invocations);
    }

    public function testHandleFormPostInvokesBitrixSaveOnPostWithUpdateMarker(): void
    {
        $component = new class ('my.module') extends GroupRights {
            public int $invocations = 0;
            public ?string $invokedWith = null;

            protected function renderBitrixRightsTable(string $moduleId): string
            {
                $this->invocations++;
                $this->invokedWith = $moduleId;

                return '';
            }
        };

        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $previousUpdate = $_REQUEST['Update'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST['Update'] = 'Y';

        try {
            $component->handleFormPost();
        } finally {
            self::restoreServer('REQUEST_METHOD', $previousMethod);
            self::restoreRequest('Update', $previousUpdate);
        }

        self::assertSame(1, $component->invocations);
        self::assertSame('my.module', $component->invokedWith);
    }

    public function testHtmlAttributesArePropagatedToWrapper(): void
    {
        $component = new class ('my.module') extends GroupRights {
            protected function renderBitrixRightsTable(string $moduleId): string
            {
                return '';
            }
        };

        $html = $component
            ->class('extra-class')
            ->attr('data-test', '1')
            ->render();

        self::assertStringContainsString('extra-class', $html);
        self::assertStringContainsString('data-test="1"', $html);
        self::assertStringContainsString('adminkit-bx-rights', $html);
    }

    private static function restoreServer(string $key, mixed $previous): void
    {
        if ($previous === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $previous;
        }
    }

    private static function restoreRequest(string $key, mixed $previous): void
    {
        if ($previous === null) {
            unset($_REQUEST[$key]);
        } else {
            $_REQUEST[$key] = $previous;
        }
    }
}
