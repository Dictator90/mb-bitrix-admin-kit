<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone\Handlers;

use Bitrix\Main\Config\Option;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use MB\Bitrix\AdminKit\Support\ComponentPostHandlers;
use MB\Bitrix\AdminKit\Support\ResponseTerminator;

final class OptionsPagePostHandler
{
    public function handle(OptionsPage $page, string $moduleId): void
    {
        $page->rememberActiveTabFromRequest();
        $siteId = $page->request->getPost('site_id') ?: '';
        $page->setErrors($this->persist($page, $moduleId, $siteId));

        $this->runComponentPostHandlers($page);

        if ($page->errors() === []) {
            $uri = $page->request->getRequestUri();
            $sep = str_contains($uri, '?') ? '&' : '?';
            LocalRedirect($uri . $sep . 'saved=1');
        }
    }

    public function handleAjax(OptionsPage $page, string $moduleId): void
    {
        $page->rememberActiveTabFromRequest();
        $siteId = $page->request->getPost('site_id') ?: '';
        $errors = $this->persist($page, $moduleId, $siteId);

        $this->runComponentPostHandlers($page);

        if ($errors === []) {
            $this->sendJsonAndExit($page, [
                'status' => 'success',
                'message' => $page->message('MB_ADMIN_KIT_OPTIONS_SAVED', 'Settings saved'),
            ]);
        }

        $this->sendJsonAndExit($page, [
            'status' => 'error',
            'message' => $page->message('MB_ADMIN_KIT_OPTIONS_SAVE_ERROR', 'Save failed'),
            'errors' => $errors,
        ]);
    }

    /**
     * Run any component-level POST handlers (e.g. GroupRights, which delegates
     * its save to Bitrix's group_rights.php). Must run for both sync and async
     * flows — async short-circuits before render, so the render-time side
     * effect never fires.
     */
    protected function runComponentPostHandlers(OptionsPage $page): void
    {
        $errors = $page->errors();
        ComponentPostHandlers::runAll(
            iterator_to_array($page->components()),
            static function (string $error) use (&$errors): void {
                $errors[] = $error;
            },
        );
        $page->setErrors($errors);
    }

    public function handleReactive(OptionsPage $page, string $moduleId): void
    {
        $fields = $page->collectEditableFields();
        $siteId = $page->request->getPost('site_id') ?: '';
        $formData = [];

        foreach ($fields as $field) {
            $raw = $page->request->getPost($field->getColumn());
            if ($raw !== null) {
                $formData[$field->getColumn()] = $field->serializePostValue($raw);
                continue;
            }

            $stored = (string)Option::get($moduleId, $field->getColumn(), (string)($field->getDefault() ?? ''), $siteId);
            $formData[$field->getColumn()] = $stored !== ''
                ? $page->unserializeOptionValue($field, $stored)
                : $field->serializePostValue($field->getDefault());
        }
        foreach ($_POST as $key => $rawValue) {
            if (!is_string($key) || $key === '' || array_key_exists($key, $formData)) {
                continue;
            }

            $formData[$key] = $rawValue;
        }

        $result = [];
        foreach ($fields as $field) {
            if (!method_exists($field, 'hasDependency') || !$field->hasDependency()) {
                continue;
            }
            $field->{'applyDependency'}($formData);
            $result[$field->getColumn()] = [
                'html' => $field->renderFormField($formData[$field->getColumn()] ?? null, $formData),
            ];
        }

        ResponseTerminator::clearOutputBuffers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'fields' => $result]);
        ResponseTerminator::terminate();
    }

    public function rejectInvalidSessid(OptionsPage $page): void
    {
        $page->markSessidRejected();

        if ($page->isAjaxRequest()) {
            $this->sendJsonAndExit($page, [
                'status' => 'error',
                'message' => $page->message(
                    'MB_ADMIN_KIT_OPTIONS_SESSION_EXPIRED',
                    'Session expired. Refresh the page and try again.',
                ),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function persist(OptionsPage $page, string $moduleId, string $siteId): array
    {
        $errors = [];

        foreach ($page->collectEditableFields() as $field) {
            $value = $field->serializePostValue($page->resolvePostedFieldValue($field->getColumn()));
            $fieldErrors = $field->{'runValidation'}($value);

            if ($fieldErrors !== []) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            $this->persistOptionValue($page, $moduleId, $field, $value, $siteId);
        }

        return $errors;
    }

    public function persistOptionValue(
        OptionsPage $page,
        string $moduleId,
        FieldContract $field,
        mixed $value,
        string $siteId,
    ): void {
        if (!$this->shouldPersistOptionValue($value)) {
            if (method_exists($field, 'preserveStoredValueWhenEmpty') && $field->preserveStoredValueWhenEmpty()) {
                return;
            }

            Option::delete($moduleId, ['name' => $field->getColumn(), 'site_id' => $siteId]);

            return;
        }

        Option::set($moduleId, $field->getColumn(), $page->serializeOptionValue($field, $value), $siteId);
    }

    protected function shouldPersistOptionValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return !(is_array($value) && $value === []);
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function sendJsonAndExit(OptionsPage $page, array $payload): void
    {
        ResponseTerminator::clearOutputBuffers();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ResponseTerminator::terminate();
    }
}
