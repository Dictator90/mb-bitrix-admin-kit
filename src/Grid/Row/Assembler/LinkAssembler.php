<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row\Assembler;

use Closure;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class LinkAssembler implements FieldAssembler
{
    /**
     * @param Closure|null $urlResolver fn($value, $rowData): string
     */
    public function __construct(
        protected array $columnIds,
        protected bool $newTab = true,
        protected ?Closure $urlResolver = null,
    ) {
    }

    public function processRow(array $row): array
    {
        foreach ($this->columnIds as $id) {
            $raw = $row['data'][$id] ?? null;
            $url = $this->urlResolver
                ? ($this->urlResolver)($raw, $row['data'])
                : (string)$raw;

            $target = $this->newTab ? ' target="_blank"' : '';
            $label = htmlspecialcharsbx((string)$raw);
            $row['columns'][$id] = '<a href="' . htmlspecialcharsbx($url) . '"' . $target . '>' . $label . '</a>';
        }

        return $row;
    }
}
