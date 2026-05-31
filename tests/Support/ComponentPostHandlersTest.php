<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\PostHandlerContract;
use MB\Bitrix\AdminKit\Support\ComponentPostHandlers;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ComponentPostHandlersTest extends TestCase
{
    public function testRunAllInvokesHandlersAcrossTabsAndBoxes(): void
    {
        $a = $this->spyHandler();
        $b = $this->spyHandler();
        $c = $this->spyHandler();

        $components = [
            $a,
            Box::make('Box', [$b]),
            Tabs::make([
                Tab::make('Visible')->fields($c),
            ]),
        ];

        ComponentPostHandlers::runAll($components);

        self::assertSame(1, $a->calls);
        self::assertSame(1, $b->calls);
        self::assertSame(1, $c->calls);
    }

    public function testRunAllSkipsHandlersInHiddenTabs(): void
    {
        $visible = $this->spyHandler();
        $hidden = $this->spyHandler();

        $components = [
            Tabs::make([
                Tab::make('Visible')->fields($visible),
                Tab::make('Hidden')->canSee(false)->fields($hidden),
            ]),
        ];

        ComponentPostHandlers::runAll($components);

        self::assertSame(1, $visible->calls);
        self::assertSame(0, $hidden->calls);
    }

    public function testRunAllForwardsHandlerErrorsToSink(): void
    {
        $failing = new class () implements ComponentContract, PostHandlerContract {
            public function render(): string
            {
                return '';
            }

            public function handleFormPost(): void
            {
                throw new RuntimeException('boom');
            }
        };
        $ok = $this->spyHandler();

        $errors = [];

        ComponentPostHandlers::runAll(
            [$failing, $ok],
            static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        self::assertSame(['boom'], $errors);
        self::assertSame(1, $ok->calls, 'subsequent handlers must still run after a failure');
    }

    public function testCollectFindsHandlersInsideNestedContainers(): void
    {
        $handler = $this->spyHandler();

        $tree = [
            Box::make('Outer', [
                Box::make('Inner', [$handler]),
            ]),
        ];

        $collected = ComponentPostHandlers::collect($tree);

        self::assertCount(1, $collected);
        self::assertSame($handler, $collected[0]);
    }

    private function spyHandler(): SpyComponentPostHandler
    {
        return new SpyComponentPostHandler();
    }
}

final class SpyComponentPostHandler implements ComponentContract, PostHandlerContract
{
    public int $calls = 0;

    public function render(): string
    {
        return '';
    }

    public function handleFormPost(): void
    {
        $this->calls++;
    }
}
