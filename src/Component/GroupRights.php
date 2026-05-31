<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component;

use MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent;
use MB\Bitrix\AdminKit\Contracts\UI\PostHandlerContract;
use MB\Bitrix\AdminKit\Exceptions\AdminKitException;
use MB\Bitrix\AdminKit\Support\DataWrapper;

/**
 * Renders the native Bitrix module group rights editor (the same table shown
 * on the «Доступ» tab of standard module admin pages).
 *
 * Saving is delegated to Bitrix: the included `group_rights.php` script both
 * renders the table and, when the parent admin form is POSTed with
 * `Update=Y`, persists the new rights via `CModule::SetGroupRight()` as a
 * side effect of being re-included during the post-save render pass.
 *
 * Usage:
 *
 *   Tabs::make([
 *       Tab::make('Доступы')->fields(
 *           BitrixGroupRights::make('my.module'),
 *       ),
 *   ])
 *
 * If no module ID is passed, the `ADMIN_MODULE_NAME` constant is used as a
 * fallback (matches Bitrix's own convention inside module admin scripts).
 *
 * @phpstan-consistent-constructor
 */
class GroupRights extends AbstractLayoutComponent implements PostHandlerContract
{
    protected ?string $moduleId;

    protected bool $emitUpdateMarker = true;

    public function __construct(?string $moduleId = null)
    {
        parent::__construct([]);
        $this->moduleId = $moduleId;
    }

    public static function make(?string $moduleId = null): static
    {
        return new static($moduleId);
    }

    /**
     * Disable the hidden `Update=Y` input that triggers Bitrix's native save
     * on the next request. Use when saving is driven by an external mechanism
     * (custom button, separate page, etc.).
     */
    public function withoutSaveTrigger(): static
    {
        $this->emitUpdateMarker = false;

        return $this;
    }

    public function withItem(?DataWrapper $item): static
    {
        return $this;
    }

    public function render(): string
    {
        $moduleId = $this->resolveModuleId();
        $content = $this->renderBitrixRightsTable($moduleId);

        $class = $this->buildClassAttr(['adminkit-bx-rights']);
        $style = $this->buildStyleAttr();
        $attrs = $this->buildExtraAttrs();

        $hidden = $this->emitUpdateMarker
            ? '<input type="hidden" name="Update" value="Y">'
            : '';

        return '<div' . $class . $style . $attrs . '>'
            . $hidden
            . '<table class="adminkit-bx-rights__table">' . $content . '</table>'
            . '</div>';
    }

    /** @return list<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract> */
    public function extractFields(): array
    {
        return [];
    }

    /**
     * Trigger Bitrix's native rights save without producing output.
     *
     * Invoked by the page POST handler so async/AJAX saves persist rights too —
     * the rendering side-effect path doesn't fire when the form short-circuits
     * with a JSON response.
     */
    public function handleFormPost(): void
    {
        if (empty($_REQUEST['Update'])) {
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        $this->renderBitrixRightsTable($this->resolveModuleId());
    }

    protected function resolveModuleId(): string
    {
        if ($this->moduleId !== null && $this->moduleId !== '') {
            return $this->moduleId;
        }

        if (defined('ADMIN_MODULE_NAME')) {
            $constant = (string)constant('ADMIN_MODULE_NAME');
            if ($constant !== '') {
                return $constant;
            }
        }

        throw new AdminKitException(
            'BitrixGroupRights: module id is not set. Pass it explicitly via '
            . 'BitrixGroupRights::make($moduleId) or define ADMIN_MODULE_NAME.'
        );
    }

    /**
     * Includes Bitrix's standard `group_rights.php` script under the requested
     * module id and returns its rendered HTML. Mirrors the contract Bitrix
     * itself uses inside module admin pages (globals must be in scope so the
     * included script can read/write the rights table).
     *
     * @codeCoverageIgnore – touches Bitrix globals and the filesystem.
     */
    protected function renderBitrixRightsTable(string $moduleId): string
    {
        global $APPLICATION, $REQUEST_METHOD, $RIGHTS, $SITES, $GROUPS;

        $module_id = $moduleId;
        $Update = !empty($_REQUEST['Update']) ? 'Y' : '';
        $bxsessid = function_exists('bitrix_sessid') ? bitrix_sessid() : '';

        $path = ($_SERVER['DOCUMENT_ROOT'] ?? '')
            . '/bitrix/modules/main/admin/group_rights.php';

        if (!is_file($path)) {
            return '';
        }

        ob_start();
        include $path;

        return (string)ob_get_clean();
    }
}
