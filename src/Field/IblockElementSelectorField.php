<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Loader;
use CIBlockElement;
use Closure;

class IblockElementSelectorField extends EntitySelectorField
{
    protected int $iblockId = 0;

    public function __construct(string $label, ?string $column = null, int $iblockId = 0)
    {
        parent::__construct($label, $column);
        $this->iblockId($iblockId);
    }

    public static function make(string $label, ?string $column = null, int $iblockId = 0): static
    {
        return new static($label, $column, $iblockId);
    }

    public function iblockId(int $iblockId): static
    {
        $this->iblockId = $iblockId;
        $this->resetEntities()->entityId('iblock-element', ['iblockId' => $iblockId]);
        $this->resolveLabels($this->makeLabelResolver($iblockId));

        return $this;
    }

    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static
    {
        foreach ((array)$sourceColumns as $col) {
            $builtIn = function (self $field, mixed $val): void {
                $field->iblockId((int)$val);
            };
            parent::dependsOn($col, $modifier ?? $builtIn);
        }

        return $this;
    }

    protected function makeLabelResolver(int $iblockId): Closure
    {
        return static function (array $ids) use ($iblockId): array {
            if (!class_exists(Loader::class) || !Loader::includeModule('iblock')) {
                return [];
            }

            $filter = ['@ID' => $ids];
            if ($iblockId > 0) {
                $filter['IBLOCK_ID'] = $iblockId;
            }

            $result = [];
            $rs = CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME']);
            while ($row = $rs->Fetch()) {
                $result[(string)$row['ID']] = $row['NAME'] ?: (string)$row['ID'];
            }

            return $result;
        };
    }
}
