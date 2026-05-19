<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row\Assembler;

use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class SwitcherAssembler implements FieldAssembler
{
    public function __construct(
        protected array $columnIds,
        protected string $checkedValue = 'Y',
        protected string $checkedLabel = '✓',
        protected string $uncheckedLabel = '—',
    ) {
    }

    public function processRow(array $row): array
    {
        foreach ($this->columnIds as $id) {
            $raw = $row['data'][$id] ?? null;
            $isChecked = Switcher::isCheckedValue($raw, $this->checkedValue);
            $color = $isChecked ? '#4caf50' : '#aaa';
            $icon = $isChecked ? '●' : '○';
            $row['columns'][$id] = '<span style="color:' . $color . ';font-size:14px;" title="' . htmlspecialcharsbx(
                (string)$raw
            ) . '">' . $icon . '</span>';
        }

        return $row;
    }
}
