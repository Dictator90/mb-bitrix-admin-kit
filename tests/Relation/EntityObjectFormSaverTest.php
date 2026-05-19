<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Relation\EntityObjectFormSaver;
use PHPUnit\Framework\TestCase;

final class EntityObjectFormSaverTest extends TestCase
{
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
}
