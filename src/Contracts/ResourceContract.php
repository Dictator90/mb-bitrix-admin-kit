<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;

/**
 * Aggregate resource contract for ORM-backed admin sections.
 *
 * Internal code may depend on narrower contracts in {@see \MB\Bitrix\AdminKit\Contracts\Resource}.
 */
interface ResourceContract extends
    \MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract
{
}
