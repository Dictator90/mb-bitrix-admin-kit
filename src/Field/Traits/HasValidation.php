<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Traits;

use Closure;
use MB\Bitrix\AdminKit\Support\Validation\Rules;

trait HasValidation
{
    protected bool $required = false;

    /** @var Closure[] */
    protected array $validators = [];

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function validate(mixed $value): array|static
    {
        if ($value instanceof Closure) {
            $this->validators[] = $value;
            return $this;
        }

        return $this->runValidation($value);
    }

    public function minLength(int $min, string $message = ''): static
    {
        return $this->validate(Rules::minLength($min, $message));
    }

    public function maxLength(int $max, string $message = ''): static
    {
        return $this->validate(Rules::maxLength($max, $message));
    }

    public function email(string $message = ''): static
    {
        return $this->validate(Rules::email($message));
    }

    public function url(string $message = ''): static
    {
        return $this->validate(Rules::url($message));
    }

    public function numeric(string $message = ''): static
    {
        return $this->validate(Rules::numeric($message));
    }

    public function min(float|int $min, string $message = ''): static
    {
        return $this->validate(Rules::min($min, $message));
    }

    public function max(float|int $max, string $message = ''): static
    {
        return $this->validate(Rules::max($max, $message));
    }

    public function pattern(string $regex, string $message = ''): static
    {
        return $this->validate(Rules::pattern($regex, $message));
    }

    public function in(array $allowed, string $message = ''): static
    {
        return $this->validate(Rules::in($allowed, $message));
    }

    /**
     * @return string[] validation errors
     */
    public function runValidation(mixed $value): array
    {
        $errors = [];

        if ($this->required && ($value === null || $value === '')) {
            $errors[] = "Поле \"{$this->getLabel()}\" обязательно для заполнения";
        }

        foreach ($this->validators as $validator) {
            $result = $validator($value);
            if ($result === false) {
                $errors[] = "Поле \"{$this->getLabel()}\" содержит некорректное значение";
            } elseif (is_string($result) && $result !== '') {
                $errors[] = $result;
            }
        }

        return $errors;
    }
}
