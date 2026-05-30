<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Bitrix\Main\Loader;

/**
 * Bitrix CHTMLEditor (BXHtmlEditor) configuration and rendering.
 *
 * @mixin \MB\Bitrix\AdminKit\Field\Textarea
 */
trait HasHtmlEditor
{
    protected ?int $htmlEditorHeight = null;
    protected int|string|null $htmlEditorWidth = null;
    protected bool $htmlEditorAllowPhp = false;
    protected bool $htmlEditorLimitPhpAccess = false;
    protected bool $htmlEditorBbCode = false;
    protected ?string $htmlEditorView = null;
    protected bool $htmlEditorShowTaskbars = false;
    protected bool $htmlEditorShowComponents = false;
    protected bool $htmlEditorShowSnippets = false;
    protected bool $htmlEditorUseFileDialogs = true;
    protected bool $htmlEditorLazyLoad = false;
    protected bool $htmlEditorAskBeforeUnload = false;
    protected bool $htmlEditorSetFocusOnShow = false;
    protected ?bool $htmlEditorUploadImagesFromClipboard = null;
    protected ?string $htmlEditorPlaceholder = null;
    protected ?string $htmlEditorSiteId = null;
    protected ?string $htmlEditorTemplateId = null;
    protected ?string $htmlEditorFontSize = null;
    protected ?string $htmlEditorIframeCss = null;
    protected ?string $htmlEditorCustomId = null;
    /** @var list<array<string, mixed>>|null */
    protected ?array $htmlEditorControlsMap = null;
    /** @var array<string, mixed>|null */
    protected ?array $htmlEditorComponentFilter = null;
    protected bool $htmlEditorAutoResize = false;
    protected ?int $htmlEditorAutoResizeOffset = null;
    protected ?int $htmlEditorAutoResizeMaxHeight = null;
    protected bool $htmlEditorAutoResizeSaveSize = true;
    protected bool $htmlEditorShowNodeNavi = false;
    protected ?string $htmlEditorRelPath = null;
    protected ?int $htmlEditorMinBodyHeight = null;
    protected ?int $htmlEditorMinBodyWidth = null;
    protected ?int $htmlEditorNormalBodyWidth = null;
    protected ?bool $htmlEditorAutoLink = null;
    protected bool $htmlEditorDisplay = true;
    protected ?string $htmlEditorBeforeUnloadMessage = null;

    public function height(int $pixels): static
    {
        $this->htmlEditorHeight = max(100, $pixels);

        return $this;
    }

    public function width(int|string $width): static
    {
        $this->htmlEditorWidth = $width;

        return $this;
    }

    public function allowPhp(bool $allow = true): static
    {
        $this->htmlEditorAllowPhp = $allow;

        return $this;
    }

    public function limitPhpAccess(bool $limit = true): static
    {
        $this->htmlEditorLimitPhpAccess = $limit;

        return $this;
    }

    public function bbCode(bool $enable = true): static
    {
        $this->htmlEditorBbCode = $enable;

        return $this;
    }

    /**
     * @param 'wysiwyg'|'code'|'split' $mode
     */
    public function view(string $mode): static
    {
        $this->htmlEditorView = match ($mode) {
            'wysiwyg', 'code', 'split' => $mode,
            default => 'wysiwyg',
        };

        return $this;
    }

    public function showTaskbar(bool $show = true): static
    {
        $this->htmlEditorShowTaskbars = $show;

        return $this;
    }

    public function showComponents(bool $show = true): static
    {
        $this->htmlEditorShowComponents = $show;

        return $this;
    }

    public function showSnippets(bool $show = true): static
    {
        $this->htmlEditorShowSnippets = $show;

        return $this;
    }

    public function useFileDialogs(bool $use = true): static
    {
        $this->htmlEditorUseFileDialogs = $use;

        return $this;
    }

    public function lazyLoad(bool $lazy = true): static
    {
        $this->htmlEditorLazyLoad = $lazy;

        return $this;
    }

    public function askBeforeUnload(bool $ask = true): static
    {
        $this->htmlEditorAskBeforeUnload = $ask;

        return $this;
    }

    public function setFocusOnShow(bool $focus = true): static
    {
        $this->htmlEditorSetFocusOnShow = $focus;

        return $this;
    }

    public function uploadImagesFromClipboard(bool $enable = true): static
    {
        $this->htmlEditorUploadImagesFromClipboard = $enable;

        return $this;
    }

    public function editorPlaceholder(string $text): static
    {
        $this->htmlEditorPlaceholder = $text;

        return $this;
    }

    public function siteId(string $siteId): static
    {
        $this->htmlEditorSiteId = $siteId;

        return $this;
    }

    public function templateId(string $templateId): static
    {
        $this->htmlEditorTemplateId = $templateId;

        return $this;
    }

    public function fontSize(string $size): static
    {
        $this->htmlEditorFontSize = $size;

        return $this;
    }

    public function iframeCss(string $css): static
    {
        $this->htmlEditorIframeCss = $css;

        return $this;
    }

    public function editorId(string $id): static
    {
        $this->htmlEditorCustomId = $id;

        return $this;
    }

    /**
     * @param list<array<string, mixed>> $controls
     */
    public function controlsMap(array $controls): static
    {
        $this->htmlEditorControlsMap = $controls;

        return $this;
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function componentFilter(array $filter): static
    {
        $this->htmlEditorComponentFilter = $filter;

        return $this;
    }

    public function autoResize(bool $enable = true, ?int $maxHeight = null, ?int $offset = null): static
    {
        $this->htmlEditorAutoResize = $enable;
        $this->htmlEditorAutoResizeMaxHeight = $maxHeight;
        $this->htmlEditorAutoResizeOffset = $offset;

        return $this;
    }

    public function autoResizeSaveSize(bool $save = true): static
    {
        $this->htmlEditorAutoResizeSaveSize = $save;

        return $this;
    }

    public function showNodeNavi(bool $show = true): static
    {
        $this->htmlEditorShowNodeNavi = $show;

        return $this;
    }

    public function relPath(string $path): static
    {
        $this->htmlEditorRelPath = $path;

        return $this;
    }

    public function minBodySize(?int $width = null, ?int $height = null): static
    {
        if ($width !== null) {
            $this->htmlEditorMinBodyWidth = $width;
        }
        if ($height !== null) {
            $this->htmlEditorMinBodyHeight = $height;
        }

        return $this;
    }

    public function normalBodyWidth(int $width): static
    {
        $this->htmlEditorNormalBodyWidth = $width;

        return $this;
    }

    public function autoLink(bool $enable = true): static
    {
        $this->htmlEditorAutoLink = $enable;

        return $this;
    }

    public function hiddenUntilInit(bool $hidden = true): static
    {
        $this->htmlEditorDisplay = !$hidden;

        return $this;
    }

    public function beforeUnloadMessage(string $message): static
    {
        $this->htmlEditorBeforeUnloadMessage = $message;

        return $this;
    }

    protected function isHtmlEditorAvailable(): bool
    {
        return Loader::includeModule('fileman') && class_exists(\CHTMLEditor::class);
    }

    protected function buildHtmlEditorId(string $inputName): string
    {
        if ($this->htmlEditorCustomId !== null && $this->htmlEditorCustomId !== '') {
            return $this->htmlEditorCustomId;
        }

        $id = 'adminkit_html_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $inputName);
        $id = trim($id, '_');

        if ($id === '' || $id === 'adminkit_html_') {
            return 'adminkit_html_editor';
        }

        return mb_substr($id, 0, 50);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildHtmlEditorParams(
        string $inputName,
        string $value,
        int $rows,
        ?string $fieldPlaceholder = null,
    ): array {
        $editorId = $this->buildHtmlEditorId($inputName);

        $params = [
            'id' => $editorId,
            'name' => $editorId,
            'inputName' => $inputName,
            'content' => $value,
            'height' => $this->htmlEditorHeight ?? max(200, $rows * 22),
            'bAllowPhp' => $this->htmlEditorAllowPhp,
            'limitPhpAccess' => $this->htmlEditorLimitPhpAccess,
            'bbCode' => $this->htmlEditorBbCode,
            'showTaskbars' => $this->htmlEditorShowTaskbars,
            'showComponents' => $this->htmlEditorShowComponents,
            'showSnippets' => $this->htmlEditorShowSnippets,
            'useFileDialogs' => $this->htmlEditorUseFileDialogs,
            'lazyLoad' => $this->htmlEditorLazyLoad,
            'askBeforeUnloadPage' => $this->htmlEditorAskBeforeUnload,
            'setFocusAfterShow' => $this->htmlEditorSetFocusOnShow,
            'showNodeNavi' => $this->htmlEditorShowNodeNavi,
            'autoResize' => $this->htmlEditorAutoResize,
            'display' => $this->htmlEditorDisplay,
        ];

        if ($this->htmlEditorWidth !== null) {
            $params['width'] = $this->htmlEditorWidth;
        }
        if ($this->htmlEditorView !== null) {
            $params['view'] = $this->htmlEditorView;
        }
        if ($this->htmlEditorUploadImagesFromClipboard !== null) {
            $params['uploadImagesFromClipboard'] = $this->htmlEditorUploadImagesFromClipboard;
        }
        if ($this->htmlEditorPlaceholder !== null) {
            $params['placeholder'] = $this->htmlEditorPlaceholder;
        } elseif ($fieldPlaceholder !== null && $fieldPlaceholder !== '') {
            $params['placeholder'] = $fieldPlaceholder;
        }
        if ($this->htmlEditorSiteId !== null) {
            $params['siteId'] = $this->htmlEditorSiteId;
        }
        if ($this->htmlEditorTemplateId !== null) {
            $params['templateId'] = $this->htmlEditorTemplateId;
        }
        if ($this->htmlEditorFontSize !== null) {
            $params['fontSize'] = $this->htmlEditorFontSize;
        }
        if ($this->htmlEditorIframeCss !== null) {
            $params['iframeCss'] = $this->htmlEditorIframeCss;
        }
        if ($this->htmlEditorControlsMap !== null) {
            $params['controlsMap'] = $this->htmlEditorControlsMap;
        }
        if ($this->htmlEditorComponentFilter !== null) {
            $params['componentFilter'] = $this->htmlEditorComponentFilter;
        }
        if ($this->htmlEditorAutoResizeOffset !== null) {
            $params['autoResizeOffset'] = $this->htmlEditorAutoResizeOffset;
        }
        if ($this->htmlEditorAutoResizeMaxHeight !== null) {
            $params['autoResizeMaxHeight'] = $this->htmlEditorAutoResizeMaxHeight;
        }
        if (!$this->htmlEditorAutoResizeSaveSize) {
            $params['autoResizeSaveSize'] = false;
        }
        if ($this->htmlEditorRelPath !== null) {
            $params['relPath'] = $this->htmlEditorRelPath;
        }
        if ($this->htmlEditorMinBodyHeight !== null) {
            $params['minBodyHeight'] = $this->htmlEditorMinBodyHeight;
        }
        if ($this->htmlEditorMinBodyWidth !== null) {
            $params['minBodyWidth'] = $this->htmlEditorMinBodyWidth;
        }
        if ($this->htmlEditorNormalBodyWidth !== null) {
            $params['normalBodyWidth'] = $this->htmlEditorNormalBodyWidth;
        }
        if ($this->htmlEditorAutoLink !== null) {
            $params['autoLink'] = $this->htmlEditorAutoLink;
        }
        if ($this->htmlEditorBeforeUnloadMessage !== null) {
            $params['beforeUnloadMessage'] = $this->htmlEditorBeforeUnloadMessage;
        }

        return $params;
    }

    protected function renderHtmlEditor(
        string $inputName,
        string $value,
        int $rows,
        ?string $fieldPlaceholder = null,
    ): string {
        if (!$this->isHtmlEditorAvailable()) {
            return '';
        }

        ob_start();
        (new \CHTMLEditor())->Show(
            $this->buildHtmlEditorParams($inputName, $value, $rows, $fieldPlaceholder),
        );

        return (string) ob_get_clean();
    }
}
