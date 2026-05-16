<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use CIBlockSection;

class IblockSectionSelect extends DialogSelect
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
        $this->resetEntities()->entityId('iblock-section-list', ['iblockId' => $iblockId]);
        $this->resolveLabels(static function (array $ids) use ($iblockId): array {
            if (!class_exists(Loader::class) || !Loader::includeModule('iblock')) {
                return [];
            }

            $result = [];
            $rows = SectionTable::query()
                ->setSelect(['ID', 'NAME'])
                ->where('IBLOCK_ID', $iblockId)
                ->whereIn('ID', $ids)
                ->fetchAll();

            foreach ($rows as $row) {
                $result[(string)$row['ID']] = $row['NAME'] ?: (string)$row['ID'];
            }

            return $result;
        });

        return $this;
    }
}
