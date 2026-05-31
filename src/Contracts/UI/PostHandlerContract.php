<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

/**
 * Marker for components that participate in the form's POST lifecycle
 * independently of rendering.
 *
 * The default save mechanism for most components is rendering side-effect
 * (e.g. `GroupRights` includes Bitrix's `group_rights.php` during render,
 * which both renders and saves). That works for full-page POST → render
 * round-trips, but breaks for async/AJAX saves where the page short-circuits
 * with a JSON response and never re-renders.
 *
 * Components implementing this contract get `handleFormPost()` called by the
 * page's POST handler AFTER the main entity/options save, in BOTH sync and
 * async flows.
 */
interface PostHandlerContract
{
    public function handleFormPost(): void;
}
