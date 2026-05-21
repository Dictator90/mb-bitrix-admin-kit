<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use CFile;
use MB\Bitrix\AdminKit\Grid\Row\Assembler\ImageAssembler;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

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
        $deleteLabel = LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_IMAGE_DELETE', 'delete image');
        $selectLabel = LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_IMAGE_SELECT', 'Choose image');

        if ($fileId > 0) {
            $fileInfo = CFile::GetByID($fileId)->Fetch();
            if ($fileInfo) {
                $filePath = CFile::GetFileSRC($fileInfo);
                $thumb = CFile::ResizeImageGet($fileInfo, [
                    'width' => $this->previewWidth,
                    'height' => $this->previewHeight,
                ], BX_RESIZE_IMAGE_PROPORTIONAL);
                $thumbSrc = $thumb['src'] ?? $filePath;

                $existingHtml = <<<HTML
                <div class="adminkit-image-current">
                    <a href="{$filePath}" target="_blank">
                        <img class="adminkit-image-current__preview" src="{$thumbSrc}" width="{$this->previewWidth}" height="{$this->previewHeight}">
                    </a>
                    <label>
                        <input type="checkbox" name="{$name}_delete" value="Y">
                        {$deleteLabel}
                    </label>
                </div>
                HTML;
            }
        }

        return <<<HTML
        {$existingHtml}
        <input type="hidden" name="{$name}" value="{$fileId}">
        <div class="ui-ctl ui-ctl-file-drop adminkit-image-upload">
            <input type="file" class="ui-ctl-element adminkit-image-upload__input" name="{$name}_file" accept="image/*">
            <div class="ui-ctl-label-text adminkit-image-upload__label">
                <span class="ui-icon-16 ui-icon-16-image adminkit-image-upload__icon"></span>
                {$selectLabel}
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

        $fileInfo = CFile::GetByID($fileId)->Fetch();
        if (!$fileInfo) {
            return '';
        }

        $filePath = CFile::GetFileSRC($fileInfo);
        $thumb = CFile::ResizeImageGet($fileInfo, [
            'width' => $this->previewWidth,
            'height' => $this->previewHeight,
        ], BX_RESIZE_IMAGE_PROPORTIONAL);
        $thumbSrc = $thumb['src'] ?? $filePath;

        return '<img class="adminkit-image-current__preview" src="' . htmlspecialcharsbx(
            $thumbSrc
        ) . '" width="' . $this->previewWidth . '" height="' . $this->previewHeight . '">';
    }
}
