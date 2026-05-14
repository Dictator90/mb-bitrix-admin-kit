<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use CFile;

class File extends Field
{
    protected array $allowedExtensions = [];
    protected ?int $maxFileSize = null;
    protected string $uploadDir = 'adminkit';

    public function allowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = $extensions;

        return $this;
    }

    public function maxFileSize(int $bytes): static
    {
        $this->maxFileSize = $bytes;

        return $this;
    }

    public function uploadDir(string $dir): static
    {
        $this->uploadDir = $dir;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);
        $name = htmlspecialcharsbx($this->column);
        $fileId = (int)$currentValue;
        $existingHtml = '';

        if ($fileId > 0) {
            $fileInfo = CFile::GetByID($fileId)->Fetch();
            if ($fileInfo) {
                $fileName = htmlspecialcharsbx($fileInfo['ORIGINAL_NAME'] ?? $fileInfo['FILE_NAME'] ?? '');
                $filePath = CFile::GetFileSRC($fileInfo);
                $fileSize = CFile::FormatSize((int)$fileInfo['FILE_SIZE']);
                $existingHtml = <<<HTML
                <div class="adminkit-file-current" style="margin-bottom:8px;">
                    <span class="ui-icon ui-icon-file" style="vertical-align:middle;"></span>
                    <a href="{$filePath}" target="_blank">{$fileName}</a>
                    <small style="color:#888;margin-left:8px;">{$fileSize}</small>
                    <label style="margin-left:16px;">
                        <input type="checkbox" name="{$name}_delete" value="Y">
                        удалить
                    </label>
                </div>
                HTML;
            }
        }

        $acceptAttr = '';
        if (!empty($this->allowedExtensions)) {
            $accepts = array_map(fn ($ext) => '.' . ltrim($ext, '.'), $this->allowedExtensions);
            $acceptAttr = ' accept="' . implode(',', $accepts) . '"';
        }

        $maxAttr = $this->maxFileSize ? ' data-max-size="' . $this->maxFileSize . '"' : '';

        return <<<HTML
        {$existingHtml}
        <input type="hidden" name="{$name}" value="{$fileId}">
        <div class="ui-ctl ui-ctl-file-drop" style="position:relative;">
            <input type="file" class="ui-ctl-element" name="{$name}_file"{$acceptAttr}{$maxAttr}
                style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
            <div class="ui-ctl-label-text" style="padding:8px 16px;cursor:pointer;">
                <span class="ui-icon-16 ui-icon-16-upload" style="vertical-align:middle;margin-right:4px;"></span>
                Выберите файл
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

        return htmlspecialcharsbx($fileInfo['ORIGINAL_NAME'] ?? $fileInfo['FILE_NAME'] ?? '');
    }
}
