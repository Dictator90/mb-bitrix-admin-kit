<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Loader;
use CIBlockElement;
use Closure;

/**
 * Selector for elements of a specific information block.
 *
 *   IblockElementSelect::make('Статья', 'ARTICLE_ID', iblockId: 5)
 *   IblockElementSelect::make('Товары', 'PRODUCT_IDS', iblockId: 2)->multiple()
 */
class IblockElementSelect extends EntitySelect
{
    public function __construct(string $label, ?string $column = null, int $iblockId = 0)
    {
        parent::__construct($label, $column);

        $this->entity('iblock-element-list', ['iblockId' => $iblockId]);

        $this->resolveLabels(static function (array $ids) use ($iblockId): array {
            if (!Loader::includeModule('iblock')) {
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
        });
    }

    /** Override Makeable::make() to accept the optional iblockId parameter. */
    public static function make(string $label, ?string $column = null, int $iblockId = 0): static
    {
        return new static($label, $column, $iblockId);
    }

    /**
     * Declare a dependency on an IblockSelect field.
     * When the source column value changes, entities are reset and re-registered
     * with the new iblockId so the element list updates via AJAX.
     *
     *   IblockElementSelect::make('Элемент', 'ELEMENT_ID')
     *       ->dependsOn('IBLOCK_ID')
     */
    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static
    {
        foreach ((array)$sourceColumns as $col) {
            $builtIn = static function (self $field, mixed $val): void {
                $field->resetEntities()->entity('iblock-element-list', ['iblockId' => (int)$val]);
            };
            parent::dependsOn($col, $modifier ?? $builtIn);
        }

        return $this;
    }
}
