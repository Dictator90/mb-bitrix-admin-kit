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
            $row['columns'][$id] = $this->renderImage($row['data'][$id] ?? null);
        }

        return $row;
    }

    protected function renderImage(mixed $value): string
    {
        $fileId = (int)$value;
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

        return '<img src="' . $src . '" alt="' . $alt . '" style="max-width:' . $this->width . 'px;max-height:' . $this->height . 'px;object-fit:contain;">';
    }
}
