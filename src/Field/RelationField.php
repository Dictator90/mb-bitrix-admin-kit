<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Closure;
use LogicException;
use MB\Bitrix\AdminKit\Contracts\Field\RelationFieldContract as NewRelationFieldContract;
use MB\Bitrix\AdminKit\Grid\Relations\RelationFieldContract;

abstract class RelationField extends Field implements RelationFieldContract, NewRelationFieldContract
{
    protected bool $readonly = true;
    protected ?string $tableClass = null;
    protected ?string $foreignKey = null;
    protected string $localKey = 'ID';
    protected string|Closure|null $valueResolver = null;
    /** @var array<string,mixed>|Closure|null */
    protected array|Closure|null $filter = null;
    /** @var array<string,string> */
    protected array $order = [];
    protected mixed $relationDefault = null;

    /** @param class-string $tableClass */
    public function table(string $tableClass): static
    {
        $this->tableClass = $tableClass;

        return $this;
    }

    /** @param class-string|null $dataManagerClass */
    public function relatedDataManager(?string $dataManagerClass): static
    {
        if ($dataManagerClass === null || $dataManagerClass === '') {
            return $this;
        }

        return $this->table($dataManagerClass);
    }

    public function foreignKey(string $column): static
    {
        $this->foreignKey = $column;

        return $this;
    }

    public function localKey(string $column = 'ID'): static
    {
        $this->localKey = $column;

        return $this;
    }

    public function valueResolver(?Closure $resolver): static
    {
        if ($resolver === null) {
            return $this;
        }

        return $this->value($resolver);
    }

    public function value(string|Closure $value): static
    {
        $this->valueResolver = $value;

        return $this;
    }

    /** @param array<string,mixed>|Closure $filter */
    public function filter(array|Closure|null $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    /** @param array<string,string> $order */
    public function order(array|null $order): static
    {
        $this->order = $order ?? [];

        return $this;
    }

    public function isRelationField(): bool
    {
        return true;
    }

    public function isMany(): bool
    {
        return $this->isToMany();
    }

    public function relationTableClass(): string
    {
        if ($this->tableClass === null || $this->tableClass === '') {
            throw new LogicException('Relation table class is required.');
        }

        return $this->tableClass;
    }

    public function relationForeignKey(): string
    {
        if ($this->foreignKey === null || $this->foreignKey === '') {
            throw new LogicException('Relation foreign key is required.');
        }

        return $this->foreignKey;
    }

    public function relationLocalKey(): string
    {
        return $this->localKey;
    }

    public function relationValue(): string|Closure
    {
        if ($this->valueResolver === null) {
            throw new LogicException('Relation value resolver is required.');
        }

        return $this->valueResolver;
    }

    public function relationFilter(): array|Closure|null
    {
        return $this->filter;
    }

    public function relationOrder(): array
    {
        return $this->order;
    }

    public function relationDefault(): mixed
    {
        return $this->relationDefault;
    }
}
