<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;
use MB\Bitrix\AdminKit\Resource\Traits\HasCrud;
use MB\Bitrix\AdminKit\Resource\Traits\HasLifecycleEvents;
use MB\Bitrix\AdminKit\Resource\Traits\HasPermissions;
use MB\Bitrix\AdminKit\Support\AdminString;

/**
 * Base administrative resource: identity, menu, permissions, pages, and optional CRUD.
 *
 * Legacy and custom resources may extend this class directly. New Bitrix D7 ORM CRUD
 * sections should extend {@see CrudResource} instead.
 *
 * For module settings, use {@see \MB\Bitrix\AdminKit\Pages\OptionsPage}.
 *
 * @template T of DataManager
 */
abstract class Resource implements ResourceContract
{
    use HasLifecycleEvents;
    use HasPermissions;
    use HasCrud;

    protected string $title = '';

    /** @var class-string<T>|null */
    protected ?string $dataManagerClass = null;

    protected string $primaryKey = 'ID';

    // ── Menu / routing identity ──────────────────────────────────────────

    /**
     * Unique slug used in `?page=` routing and admin menu URLs.
     * Defaults to lower-cased class short-name without "Resource" suffix.
     */
    public static function getId(): string
    {
        return AdminString::resourceId(static::class);
    }

    public static function getSort(): int
    {
        return 100;
    }

    public static function getMenuIcon(): string
    {
        return '';
    }

    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    /**
     * Return the getId() of a parent Resource/Page to nest this item in the menu.
     * Null = root-level item.
     */
    public static function getParentMenuId(): ?string
    {
        return null;
    }

    // ────────────────────────────────────────────────────────────────────


    public function group(): ?string
    {
        return static::getParentMenuId();
    }

    public function useSidePanel(): bool
    {
        return false;
    }

    public function createInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function editInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function detailInSidePanel(): bool
    {
        return $this->useSidePanel();
    }

    public function sidePanelWidth(): int
    {
        return 1100;
    }

    /** Close the slider after a successful save in IFRAME mode (async or full POST). */
    public function closeSidePanelAfterSave(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return class-string<T>|null */
    public function getDataManagerClass(): ?string
    {
        return $this->dataManagerClass ?: null;
    }
    public function dataManagerClass(): string
    {
        return $this->dataManagerClass ?? '';
    }

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    public function defaultSort(): array
    {
        return [$this->getPrimaryKey() => 'DESC'];
    }

    public function defaultFilter(): array
    {
        return [];
    }

    public function defaultSelect(): array
    {
        return [];
    }

    public function runtimeFields(): array
    {
        return [];
    }

    public function indexSelect(GridContext $context): array
    {
        return [];
    }

    public function indexFilter(GridContext $context): array
    {
        return [];
    }

    public function indexOrder(GridContext $context): array
    {
        return [];
    }

    public function indexRuntime(GridContext $context): array
    {
        return [];
    }

    public function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return $params;
    }

    public function afterIndexRows(array $rows, GridContext $context): array
    {
        return $rows;
    }

    public function mapIndexRow(array $row, GridContext $context): array
    {
        return $row;
    }

    public function modifyIndexParams(array $params, GridContext $context): array
    {
        return $params;
    }


    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function hasCrud(): bool
    {
        return $this->getDataManagerClass() !== null;
    }

    public function bulkChunkSize(): int
    {
        return 100;
    }

    public function databaseTableName(): string
    {
        $class = $this->getDataManagerClass();
        if ($class && method_exists($class, 'getTableName')) {
            return (string)$class::getTableName();
        }

        return '';
    }

    public function useTotalCount(GridContext $context): bool
    {
        return true;
    }

    public function countCacheTtl(GridContext $context): int
    {
        return 0;
    }

    public function maxPageSize(): int
    {
        return 200;
    }

    public function allowExportByFilter(): bool
    {
        return true;
    }

    public function allowExportAll(): bool
    {
        return false;
    }

    public function maxExportRows(): int
    {
        return 5000;
    }

    /**
     * @deprecated Import is not enabled in the current release. Reserved for backward compatibility.
     */
    public function maxImportRows(): int
    {
        return 1000;
    }


    // ── Field / Filter / Action definitions ──────────────────────────────

    /** @return iterable<FieldContract> */
    abstract public function indexFields(): iterable;

    /** @return iterable<FieldContract> */
    abstract public function formFields(): iterable;

    /** @return iterable<FieldContract> */
    public function detailFields(): iterable
    {
        return $this->formFields();
    }

    /** @return iterable<FilterContract> */
    public function filters(): iterable
    {
        return [];
    }

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable
    {
        return [];
    }

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable
    {
        return [];
    }

    /** @return iterable<AsyncAction> */
    public function asyncActions(): iterable
    {
        return [];
    }

    /** @return iterable<ToolbarAction|string> */
    public function toolbarActions(): iterable
    {
        return ['export'];
    }

    /** @return iterable<Tab> */
    public function formTabs(): iterable
    {
        return [];
    }

    // ── Pages ────────────────────────────────────────────────────────────

    /** @return iterable<class-string<\MB\Bitrix\AdminKit\Contracts\PageContract>> */
    public function pages(): iterable
    {
        return [
            IndexPage::class,
            FormPage::class,
            DetailPage::class,
        ];
    }

    public function indexPage(): IndexPage
    {
        $page = (new ResourcePageResolver())->resolve($this, IndexPage::pageName());

        if (!$page instanceof IndexPage) {
            throw new \LogicException('The index page must extend ' . IndexPage::class . '.');
        }

        return $page;
    }

    public function formPage(mixed $id = null): FormPage
    {
        $page = (new ResourcePageResolver())->resolve(
            $this,
            FormPage::pageName(),
            $id,
            ['mode' => $id === null ? 'create' : 'edit'],
        );

        if (!$page instanceof FormPage) {
            throw new \LogicException('The form page must extend ' . FormPage::class . '.');
        }

        return $page;
    }

    public function detailPage(mixed $id): DetailPage
    {
        $page = (new ResourcePageResolver())->resolve($this, DetailPage::pageName(), $id);

        if (!$page instanceof DetailPage) {
            throw new \LogicException('The detail page must extend ' . DetailPage::class . '.');
        }

        return $page;
    }

    // ── Grid identity ────────────────────────────────────────────────────

    public function getGridId(): string
    {
        return AdminString::gridId($this->dataManagerClass ?: static::class);
    }

    public function getFilterId(): string
    {
        return AdminString::filterId($this->dataManagerClass ?: static::class);
    }
}
