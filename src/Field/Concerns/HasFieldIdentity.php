<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Bitrix\Main\Security\Random;
use MB\Bitrix\AdminKit\Support\AdminString;

trait HasFieldIdentity
{
    protected string $id;
    protected string $column;
    protected string $label;

    protected function bootFieldIdentity(?string $label = null, ?string $column = null): void
    {
        $this->label = $label ?? '';
        $this->column = ($column !== null && $column !== '')
            ? $column
            : AdminString::safeKey($this->label);

        if ($this->column === '') {
            throw new \InvalidArgumentException('Field requires a column when the label is empty.');
        }

        $suffix = class_exists(Random::class) ? Random::getString(10) : bin2hex(random_bytes(5));
        $this->id = $this->column . '_' . $suffix;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
