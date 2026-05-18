<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field\Options;

use MB\Bitrix\AdminKit\Field\Options\ArrayOptionsResolver;
use MB\Bitrix\AdminKit\Field\Options\OptionsResolverFactory;
use MB\Bitrix\AdminKit\Field\Select;
use PHPUnit\Framework\TestCase;

final class SelectOptionsResolverTest extends TestCase
{
    public function testArrayAndCallbackOptionsAndValidation(): void
    {
        $select = Select::make('Status', 'STATUS')->options(['A' => 'Active', 'D' => 'Disabled']);
        self::assertSame(['A' => 'Active', 'D' => 'Disabled'], $select->getOptions());

        $dynamic = Select::make('Dynamic', 'DYN')->options(static fn (): array => ['X' => 'X-ray']);
        self::assertSame(['X' => 'X-ray'], $dynamic->getOptions());
        self::assertSame([], $select->runValidation('A'));
        self::assertNotEmpty($select->runValidation('UNKNOWN'));
    }

    public function testFactorySupportsResolverAndCacheDecorator(): void
    {
        $resolver = (new OptionsResolverFactory())->make(new ArrayOptionsResolver(['Y' => 'Yes']), 10);
        $options = $resolver->resolve([], Select::make('Flag', 'FLAG'));

        self::assertSame(['Y' => 'Yes'], $options);
    }
}
