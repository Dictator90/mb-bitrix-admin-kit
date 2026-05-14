<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\CrudResource;

class ProductResource extends CrudResource
{
    public function dataManagerClass(): string { return ProductTable::class; }
    public function indexFields(): iterable { return [ID::make('ID'), Text::make('Name', 'NAME')]; }
    public function formFields(): iterable { return [Text::make('Name', 'NAME')->required()]; }
    public function filters(): iterable { return [TextFilter::make('Name', 'NAME')]; }
    public function defaultSort(): array { return ['ID' => 'ASC']; }
}
