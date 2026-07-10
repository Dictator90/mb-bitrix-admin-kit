<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row\Assembler;

use CFile;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class ImageAssembler implements FieldAssembler
{
    public function __construct(
        protected array $columnIds,
        protected int $width = 40,
        protected int $height = 40,
    ) {
    }

    public function processRow(array $row): array
    {
        foreach ($this->columnIds as $id) {
            $row['columns'][$id] = $this->renderColumn($row['data'][$id] ?? null);
        }

        return $row;
    }

    protected function renderColumn(mixed $value): string
    {
        $images = [];
        foreach ($this->extractFileIds($value) as $fileId) {
            $image = $this->renderImage($fileId);
            if ($image !== '') {
                $images[] = $image;
            }
        }

        return implode('', $images);
    }

    /**
     * @return array<int, int>
     */
    protected function extractFileIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && str_contains($value, ',')) {
            $items = explode(',', $value);
        } else {
            $items = [$value];
        }

        $ids = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['ID'] ?? null;
            }

            $fileId = (int)$item;
            if ($fileId > 0) {
                $ids[] = $fileId;
            }
        }

        return $ids;
    }

    protected function renderImage(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }

        $fileInfo = CFile::GetByID($fileId)->Fetch();
        if (!$fileInfo) {
            return '';
        }

        $thumb = CFile::ResizeImageGet(
            $fileInfo,
            ['width' => $this->width, 'height' => $this->height],
            BX_RESIZE_IMAGE_PROPORTIONAL
        );

        if (!$thumb) {
            return '';
        }

        $src = htmlspecialcharsbx($thumb['src']);
        $alt = htmlspecialcharsbx($fileInfo['ORIGINAL_NAME'] ?? '');

        return '<img src="' . $src . '" alt="' . $alt . '" style="max-width:' . $this->width . 'px;max-height:' . $this->height . 'px;object-fit:contain;margin:2px;">';
    }
}
