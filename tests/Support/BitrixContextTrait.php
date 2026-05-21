<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Server;

/**
 * Provides helpers for manipulating the Bitrix HTTP context in tests
 * without using old MB_ADMIN_KIT_TEST_* globals.
 */
trait BitrixContextTrait
{
    private ?HttpRequest $originalRequest = null;

    /**
     * Reset b_option table for isolation.
     */
    protected function resetOptions(): void
    {
        Application::getConnection()->queryExecute('DELETE FROM b_option');
        // Clear internal Bitrix Option cache via reflection
        try {
            $class = new \ReflectionClass(\Bitrix\Main\Config\Option::class);
            if ($class->hasProperty('cache')) {
                $prop = $class->getProperty('cache');
                $prop->setAccessible(true);
                $prop->setValue(null, []);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Simulate an HTTP GET request.
     *
     * @param array<string, string> $get
     * @param array<string, string> $cookies
     */
    protected function setGetRequest(array $get = [], array $cookies = []): void
    {
        $this->switchRequest('GET', $get, [], $cookies);
    }

    /**
     * Simulate an HTTP POST request.
     *
     * @param array<string, mixed> $post
     * @param array<string, string> $get
     * @param array<string, string> $cookies
     * @param array<string, string> $server Extra $_SERVER vars (e.g. HTTP_X_REQUESTED_WITH)
     */
    protected function setPostRequest(array $post = [], array $get = [], array $cookies = [], array $server = []): void
    {
        $this->switchRequest('POST', $get, $post, $cookies, $server);
    }

    /**
     * Simulate an AJAX POST request (X-Requested-With: XMLHttpRequest).
     */
    protected function setAjaxPostRequest(array $post = [], array $get = [], array $cookies = []): void
    {
        $this->switchRequest('POST', $get, $post, $cookies, ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    }

    /**
     * Restore original request context after a test.
     */
    protected function restoreRequest(): void
    {
        if ($this->originalRequest !== null) {
            $ctx = Application::getInstance()->getContext();
            $ctx->initialize($this->originalRequest, $ctx->getResponse(), $ctx->getServer());
            $this->originalRequest = null;
        }
    }

    private function switchRequest(string $method, array $get, array $post, array $cookies, array $server = []): void
    {
        $app = Application::getInstance();
        $ctx = $app->getContext();

        if ($this->originalRequest === null) {
            $this->originalRequest = $ctx->getRequest();
        }

        $sessid = bitrix_sessid();
        $get = array_merge(['sessid' => $sessid], $get);
        $post = array_merge(['sessid' => $sessid], $post);

        $serverData = array_merge(
            ['REQUEST_METHOD' => $method],
            $server,
        );

        $newServer = new Server(array_merge($_SERVER, $serverData));
        $newRequest = new HttpRequest($newServer, $get, $post, $cookies, []);
        $ctx->initialize($newRequest, $ctx->getResponse(), $newServer);

        // Also sync $_POST so legacy superglobal code works
        $_POST = $post;
        $_GET = $get;
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER = array_merge($_SERVER, $serverData);
    }
}
