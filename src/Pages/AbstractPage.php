<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

/**
 * Base class for standalone admin pages — first-class menu items
 * that are NOT backed by an ORM DataManager.
 *
 * Subclass this (or OptionsPage / CustomPage) and register via AdminKitManager.
 * The manager discovers AbstractPage subclasses automatically alongside Resources.
 *
 * Usage:
 *   module('my.module')->adminKit()->getMenu('/bitrix/admin/my_settings.php');
 *   // → the page appears in the sidebar alongside any Resources
 */
abstract class AbstractPage
{
    /**
     * URL slug — used in `?page=` routing and admin menu link generation.
     * Must be unique within the module.
     */
    abstract public static function getId(): string;

    /** Human-readable menu/page title. */
    abstract public static function getTitle(): string;

    /** Sort order for admin sidebar (lower = higher). */
    public static function getSort(): int
    {
        return 100;
    }

    /** CSS icon class (e.g. 'adm-menu-settings', or empty string). */
    public static function getMenuIcon(): string
    {
        return '';
    }

    /** Whether the page appears in the admin sidebar. */
    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    /**
     * Return the getId() of a parent Resource or AbstractPage to nest this entry.
     * Null = root-level menu item.
     */
    public static function getParentMenuId(): ?string
    {
        return null;
    }

    /**
     * Render the full page content.
     * Called by AdminKitManager after Bitrix prolog has run.
     */
    abstract public function render(): void;
}
