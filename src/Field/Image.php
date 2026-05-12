<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Grid\Row\Assembler\ImageAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class Image extends File
{
    public function getFieldAssembler(): ?FieldAssembler
    {
        return new ImageAssembler([$this->column], $this->previewWidth, $this->previewHeight);
    }

    protected int $previewWidth = 80;
    protected int $previewHeight = 80;

    public function previewSize(int $width, int $height): static
    {
        $this->previewWidth = $width;
        $this->previewHeight = $height;

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $fileId = (int)$currentValue;
        $existingHtml = '';

        if ($fileId > 0) {
            $fileInfo = \CFile::GetByID($fileId)->Fetch();
            if ($fileInfo) {
                $filePath = \CFile::GetFileSRC($fileInfo);
                $thumb = \CFile::ResizeImageGet($fileInfo, [
                    'width' => $this->previewWidth,
                    'height' => $this->previewHeight,
                ], BX_RESIZE_IMAGE_PROPORTIONAL);
                $thumbSrc = $thumb['src'] ?? $filePath;

                $existingHtml = <<<HTML
                <div class="adminkit-image-current" style="margin-bottom:8px;display:flex;align-items:center;gap:12px;">
                    <a href="{$filePath}" target="_blank">
                        <img src="{$thumbSrc}" style="max-width:{$this->previewWidth}px;max-height:{$this->previewHeight}px;border:1px solid #ddd;border-radius:4px;">
                    </a>
                    <label>
                        <input type="checkbox" name="{$name}_delete" value="Y">
                        удалить изображение
                    </label>
                </div>
                HTML;
            }
        }

        return <<<HTML
        {$existingHtml}
        <input type="hidden" name="{$name}" value="{$fileId}">
        <div class="ui-ctl ui-ctl-file-drop" style="position:relative;">
            <input type="file" class="ui-ctl-element" name="{$name}_file" accept="image/*"
                style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
            <div class="ui-ctl-label-text" style="padding:8px 16px;cursor:pointer;">
                <span class="ui-icon-16 ui-icon-16-image" style="vertical-align:middle;margin-right:4px;"></span>
                Выберите изображение
            </div>
        </div>
        HTML;
    }

    public function previewValue(mixed $value): string
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return '';
        }

        $fileInfo = \CFile::GetByID($fileId)->Fetch();
        if (!$fileInfo) {
            return '';
        }

        $filePath = \CFile::GetFileSRC($fileInfo);
        $thumb = \CFile::ResizeImageGet($fileInfo, [
            'width' => $this->previewWidth,
            'height' => $this->previewHeight,
        ], BX_RESIZE_IMAGE_PROPORTIONAL);
        $thumbSrc = $thumb['src'] ?? $filePath;

        return '<img src="' . htmlspecialcharsbx($thumbSrc) . '" style="max-width:' . $this->previewWidth . 'px;max-height:' . $this->previewHeight . 'px;">';
    }
}
