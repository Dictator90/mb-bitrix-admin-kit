<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Validation;

use Closure;
use InvalidArgumentException;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class Validator
{
    protected array $errors = [];

    /**
     * Validate data against rules.
     *
     * @param array $data Input data ['field' => value, ...]
     * @param array $rules Validation rules ['field' => [Closure, ...], ...]
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            if (!is_array($fieldRules)) {
                $fieldRules = [$fieldRules];
            }

            foreach ($fieldRules as $rule) {
                if (!$rule instanceof Closure) {
                    continue;
                }

                $result = $rule($value);

                if ($result === false) {
                    $this->errors[$field][] = LocalizedMessage::get(
                        __FILE__,
                        'MB_ADMIN_KIT_VALIDATOR_INVALID_FIELD',
                        'Field #FIELD# contains an invalid value.',
                        ['#FIELD#' => (string) $field],
                    );
                } elseif (is_string($result) && $result !== '') {
                    $this->errors[$field][] = $result;
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[] Flat array of all error messages
     */
    public function getFlatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ((array)$fieldErrors as $error) {
                $flat[] = $error;
            }
        }

        return $flat;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function fails(): bool
    {
        return $this->hasErrors();
    }

    /**
     * Validate and throw an exception if validation fails.
     *
     * @throws InvalidArgumentException
     */
    public function validateOrFail(array $data, array $rules): void
    {
        if (!$this->validate($data, $rules)) {
            throw new InvalidArgumentException(implode('; ', $this->getFlatErrors()));
        }
    }

}
