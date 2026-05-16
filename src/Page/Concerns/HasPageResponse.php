<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

trait HasPageResponse
{
    public function redirect(string $url): void
    {
        LocalRedirect($url);
    }

    /** @param array<string,mixed> $payload */
    protected function sendJson(array $payload): void
    {
        $this->clearOutputBuffers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        die();
    }

    /** @param array<string,mixed> $payload */
    protected function sendJsonAndExit(array $payload): void
    {
        $this->sendJson($payload);
    }

    protected function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
