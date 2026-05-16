<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class AssetManager
{
    /** @var array<string, true> */
    private array $extensions = [];

    /** @var array<string, true> */
    private array $css = [];

    /** @var array<string, true> */
    private array $js = [];

    /** @param iterable<string>|string $extensions */
    public function addExtensions(iterable|string $extensions): self
    {
        foreach ((array)$extensions as $extension) {
            if ($extension !== '') {
                $this->extensions[$extension] = true;
            }
        }

        return $this;
    }

    public function forGrid(): self
    {
        return $this->addExtensions(['main.ui.grid', 'main.ui.filter', 'ui.buttons', 'ui.toolbar', 'mb.admin.kit']);
    }

    public function forForm(): self
    {
        return $this->addExtensions(['ui', 'ui.buttons', 'ui.toolbar', 'mb.admin.kit']);
    }

    public function forSidePanel(): self
    {
        return $this->addExtensions(['sidepanel']);
    }

    public function forEntitySelector(): self
    {
        return $this->addExtensions(['ui.entity-selector']);
    }

    public function addCss(string $path): self
    {
        $this->css[$path] = true;

        return $this;
    }

    public function addJs(string $path): self
    {
        $this->js[$path] = true;

        return $this;
    }

    /** @return array<string, list<string>> */
    public function all(): array
    {
        return [
            'extensions' => array_keys($this->extensions),
            'css' => array_keys($this->css),
            'js' => array_keys($this->js),
        ];
    }

    public function load(): void
    {
        $assets = $this->all();
        if ($assets['extensions'] !== [] && class_exists(Extension::class)) {
            Extension::load(AdminCollection::make($assets['extensions'])->all());
        }

        if (!isset($GLOBALS['APPLICATION']) || !is_object($GLOBALS['APPLICATION'])) {
            return;
        }

        foreach ($assets['css'] as $css) {
            if (method_exists($GLOBALS['APPLICATION'], 'SetAdditionalCSS')) {
                $GLOBALS['APPLICATION']->SetAdditionalCSS($css);
            }
        }

        foreach ($assets['js'] as $js) {
            if (method_exists($GLOBALS['APPLICATION'], 'AddHeadScript')) {
                $GLOBALS['APPLICATION']->AddHeadScript($js);
            }
        }
    }
}
