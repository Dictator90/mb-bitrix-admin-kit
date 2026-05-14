<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Schema;

use MB\Bitrix\AdminKit\Support\AdminCollection;

final class TableSchema
{
    /** @var array<string,array{name:string,type:string,required:bool}> */
    private array $columns = [];

    /** @var array<string,array{name:string,columns:array<int,string>}> */
    private array $indexes = [];

    private function __construct(private readonly string $tableName)
    {
    }

    public static function make(string $tableName): self
    {
        return new self($tableName);
    }

    public function tableName(): string
    {
        return $this->tableName;
    }

    public function column(string $name, string $type, bool $required = false): self
    {
        $this->columns[$name] = [
            'name' => $name,
            'type' => $type,
            'required' => $required,
        ];

        return $this;
    }

    /** @param array<int,string> $columns */
    public function index(string $name, array $columns): self
    {
        $this->indexes[$name] = [
            'name' => $name,
            'columns' => AdminCollection::make($columns)->all(),
        ];

        return $this;
    }

    /** @return array<string,array{name:string,type:string,required:bool}> */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return array<string,array{name:string,columns:array<int,string>}> */
    public function indexes(): array
    {
        return $this->indexes;
    }

    /** @return array<string,array{name:string,type:string,required:bool}> */
    public function requiredColumns(): array
    {
        return array_filter($this->columns, static fn (array $column): bool => $column['required']);
    }
}
