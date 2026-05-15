<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Relations;

use Closure;

interface RelationFieldContract
{
    public function isRelationField(): bool;

    /** @return class-string */
    public function relationTableClass(): string;

    public function relationForeignKey(): string;

    public function relationLocalKey(): string;

    public function relationValue(): string|Closure;

    /** @return array<string,mixed>|Closure|null */
    public function relationFilter(): array|Closure|null;

    /** @return array<string,string> */
    public function relationOrder(): array;

    public function isToMany(): bool;

    public function relationDefault(): mixed;
}
