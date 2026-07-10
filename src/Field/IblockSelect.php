<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Loader;
use CIBlock;

/**
 * Information-block selector — wraps the 'iblock-list' entity via mb.admin.kit DialogSelector.
 *
 *   IblockSelect::make('Инфоблок', 'IBLOCK_ID')
 */
class IblockSelect extends DialogSelect
{
    public function __construct(?string $label = null, ?string $column = null)
    {
        parent::__construct($label, $column);

        $this->entity('iblock-list');

        $this->resolveLabels(static function (array $ids): array {
            if (!Loader::includeModule('iblock')) {
                return [];
            }

            $result = [];
            $rs = CIBlock::GetList([], ['@ID' => $ids, 'ACTIVE' => 'Y'], false);
            while ($row = $rs->Fetch()) {
                $result[(string)$row['ID']] = $row['NAME'] ?: (string)$row['ID'];
            }

            return $result;
        });
    }
}
