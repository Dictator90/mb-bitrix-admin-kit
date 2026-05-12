<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use Throwable;

abstract class AsyncAction implements ActionContract
{
    protected string $id;
    protected string $label;

    public function __construct(string $id, string $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public static function make(string $id, string $label): static
    {
        return new static($id, $label);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Handle the AJAX request.
     *
     * @param array $data POST/GET data from the request
     * @return array Response data (will be JSON-encoded)
     */
    abstract public function handle(array $data): array;

    /**
     * Process and send JSON response. Call this from the component when isAjax().
     */
    public function dispatch(HttpRequest $request): never
    {
        if (!check_bitrix_sessid()) {
            $this->sendError('Недействительный токен безопасности');
        }

        try {
            $result = $this->handle($request->toArray());
            $this->sendSuccess($result);
        } catch (Throwable $e) {
            $this->sendError($e->getMessage());
        }
    }

    protected function sendSuccess(array $data = []): never
    {
        $this->sendJson(['status' => 'success', 'data' => $data]);
    }

    protected function sendError(string $message, int $code = 400): never
    {
        http_response_code($code);
        $this->sendJson(['status' => 'error', 'message' => $message]);
    }

    protected function sendJson(array $payload): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
