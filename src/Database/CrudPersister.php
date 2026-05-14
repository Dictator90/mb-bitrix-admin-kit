<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use RuntimeException;

final class CrudPersister
{
    public function create(string $dataManagerClass, array $data): DbResult
    {
        return $this->fromBitrixResult($dataManagerClass::add($data));
    }

    public function update(string $dataManagerClass, mixed $id, array $data): DbResult
    {
        return $this->fromBitrixResult($dataManagerClass::update($id, $data), $id);
    }

    public function delete(string $dataManagerClass, mixed $id): DbResult
    {
        return $this->fromBitrixResult($dataManagerClass::delete($id), $id);
    }

    public function fromBitrixResult(object $result, mixed $fallbackId = null): DbResult
    {
        if (method_exists($result, 'isSuccess') && !$result->isSuccess()) {
            return DbResult::error($this->extractErrors($result));
        }

        $id = $fallbackId;
        if (method_exists($result, 'getId')) {
            $id = $result->getId();
        }

        return DbResult::success($id);
    }

    /** @return string[] */
    private function extractErrors(object $result): array
    {
        if (method_exists($result, 'getErrorMessages')) {
            return array_values(array_map('strval', $result->getErrorMessages()));
        }

        if (method_exists($result, 'getErrors')) {
            $messages = [];
            foreach ($result->getErrors() as $error) {
                if (is_object($error) && method_exists($error, 'getMessage')) {
                    $messages[] = (string)$error->getMessage();
                    continue;
                }

                $messages[] = (string)$error;
            }

            return $messages;
        }

        return ['Bitrix ORM operation failed.'];
    }

    public function requireSuccess(DbResult $result): mixed
    {
        if ($result->isSuccess()) {
            return $result->id();
        }

        throw new RuntimeException(implode('; ', $result->errors()));
    }
}
