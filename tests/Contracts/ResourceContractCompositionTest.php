<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Contracts\DetailResourceContract;
use MB\Bitrix\AdminKit\Contracts\ExportableResourceContract;
use MB\Bitrix\AdminKit\Contracts\ExportResourceContract;
use MB\Bitrix\AdminKit\Contracts\FormResourceContract;
use MB\Bitrix\AdminKit\Contracts\IndexResourceContract;
use MB\Bitrix\AdminKit\Contracts\OrmResourceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceIdentityContract;
use MB\Bitrix\AdminKit\Contracts\ResourceMenuContract;
use MB\Bitrix\AdminKit\Contracts\ResourcePermissionContract;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use MB\Bitrix\AdminKit\Resource\Resource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ResourceContractCompositionTest extends TestCase
{
    /**
     * @return array<string, class-string>
     */
    public static function narrowContractProvider(): array
    {
        return [
            'identity' => [ResourceIdentityContract::class],
            'menu' => [ResourceMenuContract::class],
            'permissions' => [ResourcePermissionContract::class],
            'orm' => [OrmResourceContract::class],
            'index' => [IndexResourceContract::class],
            'form' => [FormResourceContract::class],
            'detail' => [DetailResourceContract::class],
            'exportable' => [ExportableResourceContract::class],
            'export' => [ExportResourceContract::class],
        ];
    }

    /** @dataProvider narrowContractProvider */
    public function testResourceContractExtendsNarrowContract(string $contract): void
    {
        self::assertTrue(is_a(ResourceContract::class, $contract, true));
    }

    public function testResourceAndCrudResourceImplementResourceContract(): void
    {
        self::assertTrue(is_subclass_of(Resource::class, ResourceContract::class));
        self::assertTrue(is_subclass_of(CrudResource::class, ResourceContract::class));
    }

    public function testResourceContractKeepsAggregateOnlyMethods(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                (new ReflectionClass(ResourceContract::class))->getMethods(),
                static fn (\ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === ResourceContract::class,
            ),
        );

        self::assertContains('asyncActions', $methods);
        self::assertContains('pages', $methods);
        self::assertContains('indexPage', $methods);
        self::assertContains('formPage', $methods);
        self::assertContains('detailPage', $methods);
        self::assertSame(['asyncActions', 'pages', 'indexPage', 'formPage', 'detailPage'], $methods);
    }
}
