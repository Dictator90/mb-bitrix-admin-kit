<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

/**
 * Дополнительные возможности тулбара index-страницы поверх кнопок (toolbarActions).
 * Маппятся на фасад Bitrix\UI\Toolbar\Facade\Toolbar в ToolbarRenderer.
 */
interface ResourceToolbarContract
{
    /** Заголовок тулбара (null — не переопределять). */
    public function toolbarTitle(): ?string;

    /** Сделать заголовок редактируемым (UI-only: сохранение нового значения не подключается). */
    public function toolbarEditableTitle(): bool;

    /** Показать звезду «в избранное» (UI-only: серверная персистентность не подключается). */
    public function toolbarFavoriteStar(): bool;

    /**
     * Параметры кнопки «копировать ссылку» (null — выключено).
     * Ключи: link, successfulCopyMessage, title.
     *
     * @return array<string,string>|null
     */
    public function toolbarCopyLink(): ?array;

    /** Произвольный HTML перед заголовком (null — нет). */
    public function toolbarBeforeTitleHtml(): ?string;

    /** Произвольный HTML после заголовка (null — нет). */
    public function toolbarAfterTitleHtml(): ?string;

    /** Произвольный HTML под заголовком (null — нет). */
    public function toolbarUnderTitleHtml(): ?string;

    /** Произвольный HTML в правой части тулбара (null — нет). */
    public function toolbarRightHtml(): ?string;
}
