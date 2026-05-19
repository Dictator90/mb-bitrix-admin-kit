<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use Closure;
use LogicException;
use MB\Bitrix\AdminKit\Contracts\Field\RelationFieldContract as NewRelationFieldContract;
use MB\Bitrix\AdminKit\Field\Field;
use MB\Bitrix\AdminKit\Grid\Relations\RelationFieldContract;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;
use MB\Bitrix\AdminKit\Relation\RelationType;

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
    protected ?string $relationName = null;
    protected ?string $relatedTableClass = null;
    protected ?string $ownerKeyName = null;
    protected ?string $relatedKeyName = null;
    protected ?string $pivotTableClass = null;
    protected ?string $foreignPivotKeyName = null;
    protected ?string $relatedPivotKeyName = null;
    protected bool $cascadeSaveEnabled = false;
    protected bool $cascadeDeleteEnabled = false;
    protected bool $orphanRemovalEnabled = false;

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

    protected function supportsInlineEdit(): bool
    {
        return false;
    }

    public function relationOrder(): array
    {
        return $this->order;
    }

    public function relationDefault(): mixed
    {
        return $this->relationDefault;
    }

    public function relation(string $name): static
    {
        $this->relationName = $name;

        return $this;
    }

    public function relationName(): ?string
    {
        return $this->relationName;
    }

    public function relatedTable(string $class): static
    {
        $this->relatedTableClass = $class;
        return $this;
    }
    public function ownerKey(string $key): static
    {
        $this->ownerKeyName = $key;
        return $this;
    }
    public function relatedKey(string $key): static
    {
        $this->relatedKeyName = $key;
        return $this;
    }
    public function pivotTable(string $class): static
    {
        $this->pivotTableClass = $class;
        return $this;
    }
    public function foreignPivotKey(string $key): static
    {
        $this->foreignPivotKeyName = $key;
        return $this;
    }
    public function relatedPivotKey(string $key): static
    {
        $this->relatedPivotKeyName = $key;
        return $this;
    }
    public function cascadeSave(bool $enabled = true): static
    {
        $this->cascadeSaveEnabled = $enabled;
        return $this;
    }
    public function cascadeDelete(bool $enabled = true): static
    {
        $this->cascadeDeleteEnabled = $enabled;
        return $this;
    }
    public function orphanRemoval(bool $enabled = true): static
    {
        $this->orphanRemovalEnabled = $enabled;
        return $this;
    }
    public function isCascadeSaveEnabled(): bool
    {
        return $this->cascadeSaveEnabled;
    }
    public function isCascadeDeleteEnabled(): bool
    {
        return $this->cascadeDeleteEnabled;
    }
    public function isOrphanRemovalEnabled(): bool
    {
        return $this->orphanRemovalEnabled;
    }

    public function hasExplicitRelationDefinition(): bool
    {
        return $this->relatedTableClass !== null || $this->pivotTableClass !== null;
    }

    public function buildExplicitRelationMetadata(string $ownerDataManagerClass): RelationMetadata
    {
        return new RelationMetadata(
            relationType: $this->relationType(),
            ownerEntity: $ownerDataManagerClass,
            relatedEntity: (string) $this->relatedTableClass,
            mediatorEntity: $this->pivotTableClass,
            foreignKey: $this->foreignKey,
            ownerKey: $this->ownerKeyName ?? $this->localKey,
            relatedKey: $this->relatedKeyName ?? 'ID',
            foreignPivotKey: $this->foreignPivotKeyName,
            relatedPivotKey: $this->relatedPivotKeyName,
            multiple: $this->isToMany(),
            cascadeSave: $this->cascadeSaveEnabled,
            cascadeDelete: $this->cascadeDeleteEnabled,
            orphanRemoval: $this->orphanRemovalEnabled,
            relationName: $this->relationName ?? $this->column,
        );
    }

    abstract public function relationType(): RelationType;
}
