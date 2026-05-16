<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\LayoutComponentContract;
use PHPUnit\Framework\TestCase;

final class ComponentContractTest extends TestCase
{
    public function testComponentContractRequiresOnlyRender(): void
    {
        $component = new class () implements ComponentContract {
            public function render(): string
            {
                return '<div>ok</div>';
            }
        };

        self::assertSame('<div>ok</div>', $component->render());
    }

    public function testLayoutComponentsUseDedicatedLayoutContract(): void
    {
        self::assertInstanceOf(LayoutComponentContract::class, Box::make([]));
    }
}
