<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Component\Layout\Column;
use MB\Bitrix\AdminKit\Component\Layout\Grid;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Relation\EntityObjectFormSaver;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class EntityObjectFormSaverTest extends TestCase
{
    public function testResolveSavedIdUsesItemIdWhenUpdateResultPrimaryIsNull(): void
    {
        $saver = new EntityObjectFormSaver();
        $method = new ReflectionMethod(EntityObjectFormSaver::class, 'resolveSavedId');
        $method->setAccessible(true);

        $entity = new class () {
            public function getId(): ?int
            {
                return null;
            }

            public function get(string $key): mixed
            {
                return null;
            }
        };

        $saveResult = new class () {
            public function getPrimary(): ?array
            {
                return null;
            }

            public function getId(): never
            {
                throw new \TypeError('count(): Argument #1 ($value) must be of type Countable|array, null given');
            }
        };

        self::assertSame('2', $method->invoke($saver, $saveResult, $entity, 'ID', '2'));
    }

    public function testSplitFieldsSeparatesOrmRelationsFromScalars(): void
    {
        $fields = [
            Text::make('Name', 'NAME'),
            BelongsToMany::make('Tags', 'TAGS')->relation('TAGS'),
            BelongsToMany::make('Legacy', 'TAG_IDS', 'TagTable'),
        ];

        [$scalar, $relations] = (new EntityObjectFormSaver())->splitFields($fields);

        self::assertCount(2, $scalar);
        self::assertCount(1, $relations);
        self::assertInstanceOf(BelongsToMany::class, $relations[0]);
        self::assertSame('TAGS', $relations[0]->getColumn());
    }

    public function testFlattenFieldsUnwrapsLayoutComponents(): void
    {
        $layout = [
            Tabs::make([
                Tab::make('Main', [
                    Grid::make([
                        Column::make([
                            Text::make('Name', 'NAME'),
                            Text::make('Description', 'DESCRIPTION'),
                        ]),
                    ]),
                ]),
                Tab::make('Hidden', [
                    Text::make('Secret', 'SECRET'),
                ])->canSee(static fn (): bool => false),
            ]),
            Text::make('Sort', 'SORT'),
        ];

        $fields = (new EntityObjectFormSaver())->flattenFields($layout);
        $columns = array_map(static fn ($field): string => $field->getColumn(), $fields);

        self::assertSame(['NAME', 'DESCRIPTION', 'SORT'], $columns);
    }
}
