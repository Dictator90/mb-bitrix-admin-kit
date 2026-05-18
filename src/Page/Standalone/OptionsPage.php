<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use JsonException;
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Page\Standalone\Handlers\OptionsPageFormRenderer;
use MB\Bitrix\AdminKit\Page\Standalone\Handlers\OptionsPagePostHandler;
use MB\Bitrix\AdminKit\Page\StandalonePage;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

/**
 * Standalone options page — saves values to `b_option` / `b_option_site`.
 *
 * Override components() and return any mix of fields, layout components, and Tabs.
 * Tab cannot render standalone — wrap it in Tabs::make([...]).
 *
 * Flat layout:
 *   protected function components(): iterable
 *   {
 *       return [
 *           Text::make('API Key', 'api_key'),
 *           Box::make('Debug', [
 *               Switcher::make('Enabled', 'debug'),
 *           ]),
 *       ];
 *   }
 *
 * Tabbed layout:
 *   protected function components(): iterable
 *   {
 *       return [
 *           Tabs::make([
 *               Tab::make('Основное', [
 *                   Text::make('API Key', 'api_key'),
 *               ])->active(),
 *
 *               Tab::make('Yandex Smart Captcha', [
 *                   Text::make('Client Key', 'ysc_client'),
 *                   Password::make('Server Key', 'ysc_server'),
 *               ])->id('ysc'),
 *           ]),
 *       ];
 *   }
 */
abstract class OptionsPage extends StandalonePage
{
    /** Module ID for Bitrix Config\Option — must be set in subclass. */
    protected string $moduleId = '';

    /** Show per-site tabs (one form per site in SiteTable). */
    protected bool $multiSite = false;

    protected array $errors = [];
    private bool $sessidRejected = false;

    public function __construct()
    {
        parent::__construct();

        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
        }
    }

    /**
     * Return the page's components: fields, layout components, and/or tabs.
     *
     * @return iterable<FieldContract|ComponentContract>
     */
    public function fields(): iterable
    {
        return [];
    }

    public function components(): iterable
    {
        $fields = $this->fields();
        if ($fields instanceof \Traversable) {
            return iterator_to_array($fields);
        }

        return $fields;
    }

    /** Whether to save/load options per-site. */
    protected function multiSite(): bool
    {
        return $this->multiSite;
    }

    public function render(): void
    {
        global $APPLICATION;

        (new AssetManager())->forForm()->addExtensions(['ui.layout-form', 'ui.hint', 'ui.alerts', 'ui.notification'])->load();

        $APPLICATION->SetTitle(static::getTitle());

        $this->renderToolbar();

        if (!$this->moduleId) {
            echo Alert::make($this->message('MB_ADMIN_KIT_OPTIONS_MODULE_ID_MISSING', 'moduleId is not configured.', [
                '#CLASS#' => static::class,
            ]), Alert::DANGER)->render();

            return;
        }

        $postHandler = new OptionsPagePostHandler();
        $formRenderer = new OptionsPageFormRenderer();

        if ($this->isPost()) {
            if (!check_bitrix_sessid()) {
                $postHandler->rejectInvalidSessid($this);
            } elseif ($this->request->getPost('adminkit_action') === 'reactive') {
                $postHandler->handleReactive($this, $this->moduleId);

                return;
            } elseif ($this->isAjaxRequest()) {
                $postHandler->handleAjax($this, $this->moduleId);

                return;
            } else {
                $postHandler->handle($this, $this->moduleId);
            }
        }

        if ($this->multiSite()) {
            $formRenderer->renderMultiSite($this, $this->moduleId);
        } else {
            $formRenderer->renderForm($this, $this->moduleId, '');
        }
    }

    protected function renderToolbar(): void
    {
        global $APPLICATION;
        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    protected function handlePost(string $moduleId): void
    {
        (new OptionsPagePostHandler())->handle($this, $moduleId);
    }

    protected function handleAjaxPost(string $moduleId): void
    {
        (new OptionsPagePostHandler())->handleAjax($this, $moduleId);
    }

    protected function handleReactivePost(string $moduleId): void
    {
        (new OptionsPagePostHandler())->handleReactive($this, $moduleId);
    }

    protected function renderMultiSite(string $moduleId): void
    {
        (new OptionsPageFormRenderer())->renderMultiSite($this, $moduleId);
    }

    protected function renderOptionsForm(string $moduleId, string $siteId): void
    {
        (new OptionsPageFormRenderer())->renderForm($this, $moduleId, $siteId);
    }

    protected function rejectInvalidSessid(): void
    {
        (new OptionsPagePostHandler())->rejectInvalidSessid($this);
    }

    /**
     * Pre-load all option values into a DataWrapper so components can resolve them.
     *
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     */
    public function buildOptionsWrapper(string $moduleId, string $siteId, array $components): DataWrapper
    {
        $data = [];
        foreach ($this->extractAllFields($components) as $field) {
            $stored = (string)Option::get(
                $moduleId,
                $field->getColumn(),
                (string)($field->getDefault() ?? ''),
                $siteId,
            );
            $data[$field->getColumn()] = $stored !== ''
                ? $this->unserializeOptionValue($field, $stored)
                : $field->getDefault();
        }

        return new DataWrapper($data);
    }

    protected function persistOptionValue(string $moduleId, FieldContract $field, mixed $value, string $siteId): void
    {
        (new OptionsPagePostHandler())->persistOptionValue($this, $moduleId, $field, $value, $siteId);
    }

    protected function tabsSessionKey(): string
    {
        return static::getId();
    }

    public function rememberActiveTabFromRequest(): void
    {
        $tabId = (string)($this->request->getPost('adminkit_active_tab') ?? '');
        if ($tabId === '') {
            return;
        }

        if (!isset($_SESSION['MB_ADMIN_KIT_ACTIVE_TAB']) || !is_array($_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'])) {
            $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'] = [];
        }

        $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'][$this->tabsSessionKey()] = $tabId;
    }

    /**
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     */
    public function resolveRememberedActiveTabId(array $components): ?string
    {
        $stored = $_SESSION['MB_ADMIN_KIT_ACTIVE_TAB'][$this->tabsSessionKey()] ?? null;

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    /**
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     * @return array<int, FieldContract|ComponentContract|Tab>
     */
    public function applyRememberedTabs(array $components): array
    {
        $storedTabId = $this->resolveRememberedActiveTabId($components);
        $result = [];

        foreach ($components as $item) {
            if ($item instanceof \MB\Bitrix\AdminKit\Component\Layout\Tabs && $item->remembersActiveTab()) {
                $result[] = $item->withRememberedActiveTab($storedTabId);
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    public function serializeOptionValue(FieldContract $field, mixed $value): string
    {
        if (method_exists($field, 'serializeOptionValue')) {
            return (string)$field->serializeOptionValue($value);
        }

        if (is_array($value)) {
            try {
                return (string)json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (JsonException) {
                return '[]';
            }
        }

        return (string)$value;
    }

    public function unserializeOptionValue(FieldContract $field, string $value): mixed
    {
        if (method_exists($field, 'unserializeOptionValue')) {
            return $field->unserializeOptionValue($value);
        }

        if ($value !== '' && ($value[0] === '[' || $value[0] === '{')) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (JsonException) {
                // Keep scalar string when stored value is not valid JSON.
            }
        }

        return $value;
    }

    public function isAjaxRequest(): bool
    {
        if ($this->request->getPost('adminkit_ajax') === 'Y') {
            return true;
        }

        return strtolower((string)$this->request->getHeader('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * @param array<string,string> $replace
     */
    public function message(string $code, string $fallback, array $replace = []): string
    {
        return LocalizedMessage::get(__FILE__, $code, $fallback, $replace);
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @param list<string> $errors */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function markSessidRejected(): void
    {
        $this->sessidRejected = true;
    }

    public function wasSessidRejected(): bool
    {
        return $this->sessidRejected;
    }

    /** @return FieldContract[] */
    public function collectEditableFields(): array
    {
        return $this->collectAllFields();
    }

    public function resolvePostedFieldValue(string $column): mixed
    {
        if ($this->request->getPost($column) !== null) {
            return $this->request->getPost($column);
        }

        return $this->request->get($column);
    }

    /**
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     * @return FieldContract[]
     */
    public function extractAllFields(array $components): array
    {
        $fields = [];
        foreach ($components as $item) {
            if ($item instanceof Tab) {
                $fields = array_merge($fields, $this->extractAllFields($item->getItems()));
            } elseif ($item instanceof FieldContainerContract) {
                $fields = array_merge($fields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }

        return $fields;
    }

    /** @return FieldContract[] */
    protected function collectAllFields(): array
    {
        $fields = $this->extractAllFields(iterator_to_array($this->components()));

        return array_values(array_filter($fields, static fn (FieldContract $f): bool => !$f->isReadOnly()));
    }

    public function buildSiteUrl(string $siteId): string
    {
        $uri = $this->request->getRequestUri();
        $parsed = parse_url($uri);
        parse_str($parsed['query'] ?? '', $query);
        $query['site_id'] = $siteId;
        unset($query['saved']);

        return ($parsed['path'] ?? '') . '?' . http_build_query($query);
    }

    /**
     * @param array{operator?:mixed,value?:mixed} $rule
     */
    public function checkVisibilityRule(array $rule, mixed $currentValue): bool
    {
        $operator = mb_strtolower(trim((string)($rule['operator'] ?? '=')));
        $expected = $rule['value'] ?? null;

        return match ($operator) {
            '!=', '<>' => (string)$currentValue !== (string)$expected,
            'in' => is_array($expected) && in_array($currentValue, $expected, true),
            'not in' => is_array($expected) && !in_array($currentValue, $expected, true),
            default => (string)$currentValue === (string)$expected,
        };
    }

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }
}
