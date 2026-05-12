<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Resource\Traits\HasCrud;
use MB\Bitrix\AdminKit\Resource\Traits\HasLifecycleEvents;
use MB\Bitrix\AdminKit\Resource\Traits\HasPermissions;

/**
 * Base for ORM-backed CRUD resources (Grid + Form + Detail pages).
 *
 * For settings/options pages, use Pages\OptionsPage instead.
 *
 * @template T of \Bitrix\Main\ORM\Data\DataManager
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
        $parts = explode('\\', static::class);
        return mb_strtolower((string)preg_replace('/Resource$/', '', end($parts)));
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

    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return class-string<T>|null */
    public function getDataManagerClass(): ?string
    {
        return $this->dataManagerClass;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function hasCrud(): bool
    {
        return $this->dataManagerClass !== null;
    }

    // ── Field / Filter / Action definitions ──────────────────────────────

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FieldContract> */
    abstract public function indexFields(): iterable;

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FieldContract> */
    abstract public function formFields(): iterable;

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FieldContract> */
    public function detailFields(): iterable
    {
        return $this->formFields();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FilterContract> */
    public function filters(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function rowActions(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function bulkActions(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Action\AsyncAction> */
    public function asyncActions(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Support\Tab> */
    public function formTabs(): iterable
    {
        return [];
    }

    // ── Pages ────────────────────────────────────────────────────────────

    public function indexPage(): IndexPage
    {
        return new IndexPage($this);
    }

    public function formPage(?int $id = null): FormPage
    {
        return new FormPage($this, $id);
    }

    public function detailPage(int $id): DetailPage
    {
        return new DetailPage($this, $id);
    }

    // ── Grid identity ────────────────────────────────────────────────────

    public function getGridId(): string
    {
        $base = $this->dataManagerClass ?: static::class;
        return 'ADMIN_KIT_' . mb_strtoupper(str_replace('\\', '_', $base));
    }

    public function getFilterId(): string
    {
        return $this->getGridId() . '_FILTER';
    }
}
