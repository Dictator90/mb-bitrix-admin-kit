<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceIdentityContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceMenuContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePagesContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use MB\Bitrix\AdminKit\Resource\Resource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ResourceContractCompositionTest extends TestCase
{
    public function testResourceContractIsCoreOnlyAggregate(): void
    {
        self::assertTrue(is_a(ResourceContract::class, ResourceIdentityContract::class, true));
        self::assertTrue(is_a(ResourceContract::class, ResourceMenuContract::class, true));
        self::assertTrue(is_a(ResourceContract::class, ResourcePagesContract::class, true));
        self::assertFalse(is_a(ResourceContract::class, DataManagerResourceContract::class, true));
        self::assertFalse(is_a(ResourceContract::class, CrudResourceContract::class, true));
    }

    public function testResourceContractDoesNotExposePersistenceMethods(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(ResourceContract::class))->getMethods(),
        );

        foreach (['findItem', 'create', 'update', 'delete', 'getDataManagerClass'] as $method) {
            self::assertNotContains($method, $methods);
        }
    }

    public function testDataManagerResourceContractIsOrmAggregate(): void
    {
        self::assertTrue(is_a(DataManagerResourceContract::class, CrudResourceContract::class, true));
        self::assertTrue(is_a(DataManagerResourceContract::class, ResourceOrmContract::class, true));
        self::assertTrue(is_a(DataManagerResourceContract::class, ResourcePersistenceContract::class, true));
    }

    public function testResourceCrudAndDataManagerImplementExpectedContracts(): void
    {
        self::assertTrue(is_subclass_of(Resource::class, ResourceContract::class));
        self::assertTrue(is_subclass_of(CrudResource::class, CrudResourceContract::class));
        self::assertTrue(is_subclass_of(DataManagerResource::class, DataManagerResourceContract::class));
        self::assertFalse(is_subclass_of(Resource::class, DataManagerResourceContract::class));
    }

    public function testCrudResourceContractExposesCrudPageEntryPoints(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(CrudResourceContract::class))->getMethods(),
        );

        foreach (['pages', 'indexPage', 'formPage', 'detailPage', 'getPages'] as $method) {
            self::assertContains($method, $methods, 'CrudResourceContract must expose ' . $method);
        }
    }
}
