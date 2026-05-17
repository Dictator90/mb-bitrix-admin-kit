<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

use Bitrix\Main\HttpRequest;

trait HasPageRequest
{
    public HttpRequest $request;

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }

    protected function isAjaxRequest(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function currentUserId(): mixed
    {
        global $USER;

        return is_object($USER) && method_exists($USER, 'GetID') ? $USER->GetID() : null;
    }
}
