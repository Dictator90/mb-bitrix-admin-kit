<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceAuthorizationContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceExportContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceFieldsContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceFiltersContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceGroupingContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceIdentityContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceMenuContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceQueryContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
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
            'permissions' => [ResourceAuthorizationContract::class],
            'orm' => [ResourceOrmContract::class],
            'persistence' => [ResourcePersistenceContract::class],
            'fields' => [ResourceFieldsContract::class],
            'filters' => [ResourceFiltersContract::class],
            'query' => [ResourceQueryContract::class],
            'grouping' => [ResourceGroupingContract::class],
            'export' => [ResourceExportContract::class],
            'crud' => [CrudResourceContract::class],
            'dataManager' => [DataManagerResourceContract::class],
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

    public function testResourceContractExposesPageAndActionEntryPoints(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(ResourceContract::class))->getMethods(),
        );

        foreach (['asyncActions', 'pages', 'indexPage', 'formPage', 'detailPage'] as $method) {
            self::assertContains($method, $methods, 'ResourceContract must expose ' . $method);
        }
    }
}
