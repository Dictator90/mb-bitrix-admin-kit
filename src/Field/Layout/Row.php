<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Layout;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;

/**
 * A horizontal group of sub-fields for {@see \MB\Bitrix\AdminKit\Field\Json}.
 *
 * Passed inside {@see \MB\Bitrix\AdminKit\Field\Json::fields()} alongside plain
 * fields to mix layouts: top-level entries stack vertically, one under another,
 * while every field inside a Row shares one horizontal line. Column widths come
 * from each field's {@see FieldContract::width()} (falling back to equal
 * fractions).
 *
 *   Json::make('Блоки', 'blocks')->fields([
 *       Row::make([
 *           Text::make('Иконка', 'icon')->width(80),
 *           Text::make('Заголовок', 'title'),
 *       ]),
 *       Textarea::make('Описание', 'description'),
 *   ])
 *
 * Row is presentational only: it carries no column or value and never appears in
 * the stored JSON — the field's data contract stays the flat leaf list.
 */
final class Row
{
    /** @var list<FieldContract> */
    private array $fields;

    /**
     * @param list<FieldContract> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = array_values($fields);
    }

    /**
     * @param list<FieldContract> $fields
     */
    public static function make(array $fields): self
    {
        return new self($fields);
    }

    /**
     * @return list<FieldContract>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
