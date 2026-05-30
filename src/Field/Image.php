<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\FileInput;
use CFile;
use Closure;
use InvalidArgumentException;

class Image extends File
{
    protected int|Closure|null $maxImageWidth = null;
    protected int|Closure|null $maxImageHeight = null;
    protected int|Closure|null $resizeType = null;

    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);

        $this->uploadType = FileInput::UPLOAD_IMAGES;
    }

    public function uploadType(string|Closure|null $type): static
    {
        return $this;
    }

    public function maxImageWidth(int|Closure|null $width): static
    {
        $this->maxImageWidth = $width;

        return $this;
    }

    public function maxImageHeight(int|Closure|null $height): static
    {
        $this->maxImageHeight = $height;

        return $this;
    }

    /**
     * Sets maximum image resolution.
     *
     * Images larger than this limit are resized before CFile::SaveFile().
     * The method does not reject oversized images by width/height.
     */
    public function maxImageSize(int|Closure|null $width, int|Closure|null $height = null): static
    {
        $this->maxImageWidth = $width;
        $this->maxImageHeight = $height;

        return $this;
    }

    /**
     * Bitrix resize type. BX_RESIZE_IMAGE_PROPORTIONAL is used by default.
     *
     * Example:
     * Image::make('Image', 'IMAGE_ID')->resizeType(BX_RESIZE_IMAGE_EXACT);
     */
    public function resizeType(int|Closure|null $resizeType): static
    {
        $this->resizeType = $resizeType;

        return $this;
    }

    protected function getDefaultUploadType(mixed $currentValue = null): string
    {
        return FileInput::UPLOAD_IMAGES;
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function saveFile(array $file, mixed $currentValue = null): ?int
    {
        $this->validateUploadError($file);
        $this->validateImageFile($file, $currentValue, false);

        $file = $this->resizeImageBeforeSave($file, $currentValue);

        parent::validateFileBeforeSave($file, $currentValue);
        $this->validateImageFile($file, $currentValue, true);

        $savedId = CFile::SaveFile($file, $this->getDir($currentValue));

        return is_numeric($savedId) && (int)$savedId > 0 ? (int)$savedId : null;
    }

    /**
     * Image-specific validation for inherited save flows.
     *
     * Max width/height are intentionally not passed to CFile::CheckImageFile().
     * maxImageSize() resizes oversized images before saving instead of rejecting them.
     *
     * @param array<string, mixed> $file
     */
    protected function validateFileBeforeSave(array $file, mixed $currentValue = null): void
    {
        parent::validateFileBeforeSave($file, $currentValue);
        $this->validateImageFile($file, $currentValue, true);
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function validateUploadError(array $file): void
    {
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->getUploadErrorMessage($errorCode));
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function validateImageFile(array $file, mixed $currentValue = null, bool $checkMaxFileSize = true): void
    {
        $error = CFile::CheckImageFile(
            $file,
            $checkMaxFileSize ? ($this->getMaxFileSize($currentValue) ?? 0) : 0,
            0,
            0,
        );

        if (is_string($error) && $error !== '') {
            throw new InvalidArgumentException($error);
        }
    }

    /**
     * Resizes uploaded image in-place before CFile::SaveFile().
     *
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    protected function resizeImageBeforeSave(array $file, mixed $currentValue = null): array
    {
        $maxWidth = $this->getMaxImageWidth($currentValue);
        $maxHeight = $this->getMaxImageHeight($currentValue);

        if ($maxWidth === null && $maxHeight === null) {
            return $file;
        }

        $tmpName = $file['tmp_name'] ?? null;
        if (!is_string($tmpName) || $tmpName === '' || !is_file($tmpName)) {
            return $file;
        }

        $imageSize = getimagesize($tmpName);
        if (!is_array($imageSize)) {
            return $file;
        }

        $width = (int)($imageSize[0] ?? 0);
        $height = (int)($imageSize[1] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return $file;
        }

        if (($maxWidth === null || $width <= $maxWidth) && ($maxHeight === null || $height <= $maxHeight)) {
            return $file;
        }

        $targetSize = $this->getResizeTargetSize($width, $height, $maxWidth, $maxHeight);
        if ($targetSize === null) {
            return $file;
        }

        $resizeResult = CFile::ResizeImage(
            $file,
            $targetSize,
            $this->getResizeType($currentValue),
        );

        if ($resizeResult === false) {
            throw new InvalidArgumentException('Failed to resize uploaded image before saving.');
        }

        $resizedTmpName = $file['tmp_name'] ?? null;
        if (is_string($resizedTmpName) && $resizedTmpName !== '' && is_file($resizedTmpName)) {
            $file['size'] = filesize($resizedTmpName) ?: 0;
        }

        return $file;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    protected function getResizeTargetSize(int $width, int $height, ?int $maxWidth, ?int $maxHeight): ?array
    {
        if ($maxWidth === null && $maxHeight === null) {
            return null;
        }

        if ($maxWidth !== null && $maxHeight !== null) {
            return [
                'width' => $maxWidth,
                'height' => $maxHeight,
            ];
        }

        if ($maxWidth !== null) {
            return [
                'width' => $maxWidth,
                'height' => max(1, (int)round($height * ($maxWidth / $width))),
            ];
        }

        return [
            'width' => max(1, (int)round($width * ($maxHeight / $height))),
            'height' => $maxHeight,
        ];
    }

    protected function getMaxImageWidth(mixed $currentValue = null): ?int
    {
        $width = $this->resolveOption($this->maxImageWidth, $currentValue);

        if ($width === null || $width === '') {
            return null;
        }

        $width = (int)$width;

        return $width > 0 ? $width : null;
    }

    protected function getMaxImageHeight(mixed $currentValue = null): ?int
    {
        $height = $this->resolveOption($this->maxImageHeight, $currentValue);

        if ($height === null || $height === '') {
            return null;
        }

        $height = (int)$height;

        return $height > 0 ? $height : null;
    }

    protected function getResizeType(mixed $currentValue = null): int
    {
        $resizeType = $this->resolveOption($this->resizeType, $currentValue);
        if ($resizeType !== null && $resizeType !== '') {
            return (int)$resizeType;
        }

        return defined('BX_RESIZE_IMAGE_PROPORTIONAL') ? BX_RESIZE_IMAGE_PROPORTIONAL : 1;
    }
}
