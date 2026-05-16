<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use Closure;

interface RelationFieldContract
{
    /**
     * @param class-string|null $dataManagerClass
     */
    public function relatedDataManager(?string $dataManagerClass): static;

    public function foreignKey(string $column): static;

    public function localKey(string $column): static;

    public function valueResolver(?Closure $resolver): static;

    public function filter(array|\Closure|null $filter): static;

    public function order(?array $order): static;

    public function isMany(): bool;
}
