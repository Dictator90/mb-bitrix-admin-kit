<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\Context;
use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\FileInput;
use CFile;
use Closure;
use Throwable;

class File extends Field
{
    /** @var array<int, string>|string|Closure */
    protected array|string|Closure $allowedExtensions = [];

    protected int|Closure|null $maxFileSize = null;

    protected string|Closure $dir = 'adminkit';

    protected bool|Closure $canUpload = true;
    protected bool|Closure $canEdit = true;
    protected bool|Closure $canDelete = true;
    protected bool|Closure $useCloud = true;
    protected bool|Closure $medialib = false;
    protected bool|Closure $fileDialog = true;

    protected int|Closure|null $maxCount = null;

    protected string|Closure|null $uploadType = null;

    protected bool|Closure $description = false;

    protected bool|Closure $deletePhysicalFiles = true;

    /**
     * When true, newly uploaded/linked files are handed to the ORM as a file
     * array instead of a pre-saved file id. Required for UserField "file"
     * columns (e.g. Highload-block UF_* files), whose save layer rejects a
     * foreign integer id but persists a fresh file array. Set automatically by
     * the persistence layer via {@see self::ormExpectsFileArray()}.
     */
    protected bool $ormExpectsFileArray = false;

    public function __construct(?string $label = null, ?string $column = null)
    {
        parent::__construct($label, $column);
    }

    public function ormExpectsFileArray(bool $value = true): static
    {
        $this->ormExpectsFileArray = $value;

        return $this;
    }

    /**
     * In file-array mode the raw POST value is passed through untouched so the
     * file array is produced by a single {@see self::normalize()} pass in the
     * pipeline. Normalizing here (the default) would run twice and a file array
     * is not idempotent under re-normalization (unlike a saved integer id).
     */
    public function serializePostValue(mixed $value): mixed
    {
        if ($this->ormExpectsFileArray) {
            return $value;
        }

        return parent::serializePostValue($value);
    }

    /**
     * @param array<int, string>|string|Closure $extensions
     */
    public function allowedExtensions(array|string|Closure $extensions): static
    {
        $this->allowedExtensions = $extensions;

        return $this;
    }

    public function maxFileSize(int|Closure|null $bytes): static
    {
        $this->maxFileSize = $bytes;

        return $this;
    }

    public function dir(string|Closure $dir): static
    {
        $this->dir = $dir;

        return $this;
    }

    public function canUpload(bool|Closure $value = true): static
    {
        $this->canUpload = $value;

        return $this;
    }

    public function canEdit(bool|Closure $value = true): static
    {
        $this->canEdit = $value;

        return $this;
    }

    public function canDelete(bool|Closure $value = true): static
    {
        $this->canDelete = $value;

        return $this;
    }

    public function useCloud(bool|Closure $value = true): static
    {
        $this->useCloud = $value;

        return $this;
    }

    public function medialib(bool|Closure $value = true): static
    {
        $this->medialib = $value;

        return $this;
    }

    public function fileDialog(bool|Closure $value = true): static
    {
        $this->fileDialog = $value;

        return $this;
    }

    public function maxCount(int|Closure|null $value): static
    {
        $this->maxCount = $value;

        return $this;
    }

    public function uploadType(string|Closure|null $type): static
    {
        $this->uploadType = $type;

        return $this;
    }

    public function description(bool|Closure $value = true): static
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Controls whether CFile::Delete() is called for files removed in FileInput.
     *
     * Keep enabled for the old behavior. Disable it if the same file IDs may be reused
     * by other entities or if deletion is handled by a saver/service after successful ORM save.
     */
    public function deletePhysicalFiles(bool|Closure $value = true): static
    {
        $this->deletePhysicalFiles = $value;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    private static bool $replaceInputPatched = false;

    public function renderFormField(mixed $value = null): string
    {
        $currentValue = $this->resolveValue($value);

        $html = (new FileInput($this->getFileInputParams($currentValue)))
            ->show($this->prepareFileValue($currentValue));

        return $html . self::renderFileInputCrashPatch();
    }

    /**
     * Guards a Bitrix core bug in {@see BX.UI.FileInput.replaceInput}
     * (bitrix/js/main/core/core_fileinput.js): after a successful upload the
     * DOM-cleanup loop walks into a sibling node without a `name` property and
     * throws `Cannot read properties of undefined (reading 'indexOf')`. The
     * uploader catches it and mislabels it as "Unexpected server response",
     * so an uploaded file (typically video) intermittently fails to attach.
     *
     * We reinstall a guarded copy of the method (adds `typeof input.name ===
     * 'string'` to the loop condition and a null-input bailout). Emitted once
     * per page, right after the first File/Image field.
     */
    private static function renderFileInputCrashPatch(): string
    {
        if (self::$replaceInputPatched) {
            return '';
        }
        self::$replaceInputPatched = true;

        return <<<'HTML'
<script data-adminkit-fileinput-patch>
(function patch(){
    if (!(window.BX && BX.UI && BX.UI.FileInput && BX.UI.FileInput.prototype)) { setTimeout(patch, 50); return; }
    var proto = BX.UI.FileInput.prototype;
    if (proto.__adminKitReplaceInputPatched) { return; }
    proto.__adminKitReplaceInputPatched = true;
    proto.replaceInput = function(item, data){
        var pointer = this.agent.getItem(item.id);
        if (!pointer || !pointer.node) { return; }
        var node = pointer.node,
            input_name = node["__replaceInputName"],
            id = item.id + 'Value',
            input = BX.findChild(node, {tagName: "INPUT", attr: {id: id}}, true),
            tmp,
            file = (data && data['file'] && data['file']['files'] && data['file']['files']['default']) ? data['file']['files']['default'] : false;
        if (!input) { return; }
        if (!input_name) { input_name = node["__replaceInputName"] = input.name; }
        if (file) {
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name + '[name]', id: input.id, value: item.name}}), input);
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name + '[type]', value: file['type']}}), input);
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name + '[tmp_name]', value: file['path']}}), input);
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name + '[size]', value: file['size']}}), input);
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name + '[error]', value: 0}}), input);
        } else {
            input.parentNode.insertBefore(BX.create("INPUT", {attrs: {type: "hidden", name: input_name, id: input.id, value: (data && data['file'] ? data['file']['uploadId'] : '')}}), input);
        }
        while (BX(input) && typeof input.name === "string" && input.name.indexOf(input_name) === 0) {
            tmp = input.nextSibling;
            BX.remove(input);
            input = tmp;
        }
        if (this.uploadParams["maxCount"] <= 1) {
            var n = BX.findChild(this.container, {tagName: "INPUT", attr: {name: input_name}}, false);
            if (n) {
                BX.adjust(n, {attrs: {disabled: true}});
                var nDelName = input_name + '_del';
                if (input_name.indexOf('[') > 0) {
                    nDelName = input_name.substr(0, input_name.indexOf('[')) + '_del' + input_name.substr(input_name.indexOf('['));
                }
                n = BX.findChild(this.container, {tagName: "INPUT", attr: {name: nDelName}}, false);
                if (n) { BX.adjust(n, {attrs: {disabled: true}}); }
            }
        }
    };
})();
</script>
HTML;
    }

    public function normalize(mixed $value): mixed
    {
        $request = Context::getCurrent()->getRequest();
        $deletedIds = $this->collectDeletedFileIds($request->getPost($this->column . '_del'));

        $values = $this->parseFileValue($value);
        foreach ($this->collectUploadedFilesFromGlobals() as $uploadedFile) {
            $values[] = $uploadedFile;
        }

        if ($values === [] && $deletedIds === []) {
            return $this->multiple ? [] : null;
        }

        $result = [];
        $seenExistingIds = [];

        foreach ($values as $key => $fileValue) {
            if ($this->isEmptyFileValue($fileValue)) {
                continue;
            }

            $existingFileId = $this->extractExistingFileId($key, $fileValue);
            if ($existingFileId > 0) {
                $seenExistingIds[] = $existingFileId;

                if (in_array($existingFileId, $deletedIds, true)) {
                    $this->deleteFile($existingFileId, $value);
                    continue;
                }

                $result[] = $existingFileId;
                continue;
            }

            $file = $this->makeFileArray($fileValue);
            if ($file === null) {
                continue;
            }

            if ($this->ormExpectsFileArray) {
                $this->validateFileBeforeSave($file, $value);
                $result[] = $file;
                continue;
            }

            $savedId = $this->saveFile($file, $value);
            if ($savedId !== null) {
                $result[] = $savedId;
            }
        }

        foreach (array_diff($deletedIds, $seenExistingIds) as $deletedId) {
            $this->deleteFile($deletedId, $value);
        }

        if (!$this->multiple) {
            return $result === [] ? null : end($result);
        }

        return array_values($result);
    }

    public function previewValue(mixed $value): string
    {
        $items = [];

        foreach ($this->parseFileValue($value) as $fileValue) {
            $fileId = $this->extractFileId($fileValue);
            if ($fileId <= 0) {
                continue;
            }

            $item = $this->renderPreviewItem($fileId);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $this->renderPreviewItems($items);
    }

    /**
     * Parse raw string/array value into an array of file IDs, paths or file arrays.
     *
     * @return array<array-key, mixed>
     */
    protected function parseFileValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $this->looksLikeFileArray($value) ? [$value] : $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            if ($value[0] === '[' || $value[0] === '{') {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        return $this->looksLikeFileArray($decoded) ? [$decoded] : $decoded;
                    }
                } catch (\JsonException) {
                    // Ignore and try other formats.
                }
            }

            if (str_contains($value, ',')) {
                return array_values(array_filter(
                    array_map('trim', explode(',', $value)),
                    static fn (string $item): bool => $item !== '',
                ));
            }

            return [$value];
        }

        return is_scalar($value) ? [$value] : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareFileValue(mixed $value): array
    {
        $result = [];

        foreach ($this->parseFileValue($value) as $fileValue) {
            $fileId = $this->extractFileId($fileValue);
            if ($fileId <= 0) {
                continue;
            }

            $fileArray = CFile::GetFileArray($fileId);
            if (is_array($fileArray)) {
                $result[$this->column . '[isset_' . $fileId . ']'] = $fileArray;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFileInputParams(mixed $currentValue = null): array
    {
        $allowUpload = $this->getUploadType($currentValue);
        $allowedExtensions = $this->getAllowedExtensions($currentValue);

        $params = [
            'id' => $this->column . '_' . Random::getString(5),
            'name' => $this->column . '[#IND#]',
            'description' => $this->isDescriptionEnabled($currentValue),
            'upload' => $this->isUploadAllowed($currentValue) && $this->isEditAllowed($currentValue),
            'allowUpload' => $allowUpload,
            'medialib' => $this->isMedialibEnabled($currentValue),
            'fileDialog' => $this->isFileDialogEnabled($currentValue),
            'cloud' => $this->isCloudEnabled($currentValue),
            'delete' => $this->isDeleteAllowed($currentValue),
            'edit' => $this->isEditAllowed($currentValue),
            'maxCount' => $this->multiple ? ($this->getMaxCount($currentValue) ?? 0) : 1,
            'maxSize' => $this->getMaxFileSize($currentValue) ?? 0,
        ];

        if ($allowUpload === FileInput::UPLOAD_EXTENTION_LIST && $allowedExtensions !== []) {
            $params['allowUploadExt'] = implode(',', $allowedExtensions);
        }

        return $params;
    }

    protected function getDefaultUploadType(mixed $currentValue = null): string
    {
        return FileInput::UPLOAD_ANY_FILES;
    }

    /**
     * @return array<int, string>
     */
    protected function getAllowedExtensions(mixed $currentValue = null): array
    {
        $extensions = $this->resolveOption($this->allowedExtensions, $currentValue);

        if (is_string($extensions)) {
            $extensions = explode(',', $extensions);
        }

        if (!is_array($extensions)) {
            return [];
        }

        $result = [];
        foreach ($extensions as $extension) {
            if (!is_scalar($extension)) {
                continue;
            }

            $extension = strtolower(trim((string)$extension));
            $extension = ltrim($extension, '.');

            if ($extension !== '') {
                $result[] = $extension;
            }
        }

        return array_values(array_unique($result));
    }

    protected function getMaxFileSize(mixed $currentValue = null): ?int
    {
        $value = $this->resolveOption($this->maxFileSize, $currentValue);

        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }

    protected function getDir(mixed $currentValue = null): string
    {
        $dir = trim((string)$this->resolveOption($this->dir, $currentValue));

        return $dir !== '' ? $dir : 'adminkit';
    }

    protected function getMaxCount(mixed $currentValue = null): ?int
    {
        $value = $this->resolveOption($this->maxCount, $currentValue);

        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }

    protected function getUploadType(mixed $currentValue = null): string
    {
        $type = $this->resolveOption($this->uploadType, $currentValue);
        if (is_string($type) && $type !== '') {
            return $type;
        }

        return $this->getAllowedExtensions($currentValue) !== []
            ? FileInput::UPLOAD_EXTENTION_LIST
            : $this->getDefaultUploadType($currentValue);
    }

    protected function isUploadAllowed(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->canUpload, $currentValue);
    }

    protected function isEditAllowed(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->canEdit, $currentValue);
    }

    protected function isDeleteAllowed(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->canDelete, $currentValue);
    }

    protected function isCloudEnabled(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->useCloud, $currentValue);
    }

    protected function isMedialibEnabled(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->medialib, $currentValue);
    }

    protected function isFileDialogEnabled(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->fileDialog, $currentValue);
    }

    protected function isDescriptionEnabled(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->description, $currentValue);
    }

    protected function shouldDeletePhysicalFiles(mixed $currentValue = null): bool
    {
        return (bool)$this->resolveOption($this->deletePhysicalFiles, $currentValue);
    }

    protected function resolveOption(mixed $value, mixed $currentValue = null): mixed
    {
        if (!$value instanceof Closure) {
            return $value;
        }

        return $value($currentValue, $this);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectUploadedFilesFromGlobals(): array
    {
        if (!isset($_FILES[$this->column]) || !is_array($_FILES[$this->column])) {
            return [];
        }

        return $this->normalizePhpFilesArray($_FILES[$this->column]);
    }

    /**
     * @param array<string, mixed> $files
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePhpFilesArray(array $files): array
    {
        if (!array_key_exists('name', $files)) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$this->normalizeSinglePhpFileArray($files)];
        }

        $result = [];
        foreach ($this->collectNestedPaths($files['name']) as $path) {
            $file = [
                'name' => $this->getNestedValue($files['name'], $path),
                'type' => $this->getNestedValue($files['type'] ?? [], $path) ?? '',
                'tmp_name' => $this->getNestedValue($files['tmp_name'] ?? [], $path) ?? '',
                'error' => $this->getNestedValue($files['error'] ?? [], $path) ?? UPLOAD_ERR_NO_FILE,
                'size' => $this->getNestedValue($files['size'] ?? [], $path) ?? 0,
            ];

            if ((int)$file['error'] === UPLOAD_ERR_NO_FILE || (string)$file['tmp_name'] === '') {
                continue;
            }

            $result[] = $file;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    protected function normalizeSinglePhpFileArray(array $file): array
    {
        return [
            'name' => $file['name'] ?? '',
            'type' => $file['type'] ?? '',
            'tmp_name' => $file['tmp_name'] ?? '',
            'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'] ?? 0,
        ];
    }

    /**
     * @param array<array-key, mixed> $value
     * @param array<int, int|string> $prefix
     * @return array<int, array<int, int|string>>
     */
    protected function collectNestedPaths(array $value, array $prefix = []): array
    {
        $paths = [];

        foreach ($value as $key => $item) {
            $path = [...$prefix, $key];

            if (is_array($item)) {
                $paths = [...$paths, ...$this->collectNestedPaths($item, $path)];
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @param array<array-key, mixed>|mixed $value
     * @param array<int, int|string> $path
     */
    protected function getNestedValue(mixed $value, array $path): mixed
    {
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @return array<int, int>
     */
    protected function collectDeletedFileIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $key => $item) {
            $keyId = $this->extractFileIdFromDeleteKey($key);
            if ($keyId > 0) {
                $ids[] = $keyId;
                continue;
            }

            $valueId = $this->extractFileIdFromDeleteKey($item);
            if ($valueId > 0) {
                $ids[] = $valueId;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function extractExistingFileId(int|string|null $key, mixed $value): int
    {
        if ($key !== null) {
            $keyId = $this->extractFileIdFromDeleteKey($key);
            if ($keyId > 0) {
                return $keyId;
            }
        }

        return $this->extractFileId($value);
    }

    protected function extractFileId(mixed $value): int
    {
        if (is_array($value) && isset($value['ID'])) {
            return (int)$value['ID'];
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        return 0;
    }

    protected function extractFileIdFromDeleteKey(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if (!is_string($value)) {
            return 0;
        }

        if (preg_match('#isset_([0-9]+)$#', $value, $match)) {
            return (int)$match[1];
        }

        return 0;
    }

    protected function isEmptyFileValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        return ($value['error'] ?? null) === UPLOAD_ERR_NO_FILE
            || (($value['name'] ?? '') === '' && ($value['tmp_name'] ?? '') === '' && !isset($value['ID']));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    protected function looksLikeFileArray(array $value): bool
    {
        return array_key_exists('ID', $value)
            || array_key_exists('tmp_name', $value)
            || array_key_exists('name', $value)
            || array_key_exists('file', $value)
            || array_key_exists('path', $value);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function makeFileArray(mixed $value): ?array
    {
        if (is_array($value)) {
            $file = FileInput::prepareFile($value);
            if (!is_array($file) && isset($value['tmp_name'])) {
                $file = $value;
            }

            return is_array($file) ? $this->normalizeTemporaryFilePath($file) : null;
        }

        if (is_string($value) && $value !== '') {
            $file = CFile::MakeFileArray($value);

            return is_array($file) ? $this->normalizeTemporaryFilePath($file) : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    protected function normalizeTemporaryFilePath(array $file): array
    {
        if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || $file['tmp_name'] === '') {
            return $file;
        }

        if (file_exists($file['tmp_name'])) {
            return $file;
        }

        if (!class_exists(\CTempFile::class)) {
            return $file;
        }

        $candidate = rtrim(\CTempFile::GetAbsoluteRoot(), '/\\') . '/' . ltrim($file['tmp_name'], '/\\');
        if (file_exists($candidate)) {
            $file['tmp_name'] = $candidate;
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function saveFile(array $file, mixed $currentValue = null): ?int
    {
        $this->validateFileBeforeSave($file, $currentValue);

        $savedId = CFile::SaveFile($file, $this->getDir($currentValue));

        return is_numeric($savedId) && (int)$savedId > 0 ? (int)$savedId : null;
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function validateFileBeforeSave(array $file, mixed $currentValue = null): void
    {
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException($this->getUploadErrorMessage($errorCode));
        }

        $allowedExtensions = $this->getAllowedExtensions($currentValue);
        $extensionList = $allowedExtensions !== [] ? implode(',', $allowedExtensions) : false;

        $error = CFile::CheckFile(
            $file,
            $this->getMaxFileSize($currentValue) ?? 0,
            false,
            $extensionList,
        );

        if (is_string($error) && $error !== '') {
            throw new \InvalidArgumentException($error);
        }
    }

    protected function deleteFile(int $fileId, mixed $currentValue = null): void
    {
        if ($fileId <= 0 || !$this->shouldDeletePhysicalFiles($currentValue)) {
            return;
        }

        try {
            CFile::Delete($fileId);
        } catch (Throwable) {
            // Some test environments or DB drivers may not support real file deletion.
        }
    }

    protected function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'Uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
            default => 'Unknown file upload error.',
        };
    }

    protected function renderPreviewItem(int $fileId): string
    {
        $fileInfo = CFile::GetByID($fileId)->Fetch();
        if (!is_array($fileInfo)) {
            return '';
        }

        return htmlspecialcharsbx((string)($fileInfo['ORIGINAL_NAME'] ?? $fileInfo['FILE_NAME'] ?? ''));
    }

    /**
     * @param array<int, string> $items
     */
    protected function renderPreviewItems(array $items): string
    {
        return implode(', ', $items);
    }
}
