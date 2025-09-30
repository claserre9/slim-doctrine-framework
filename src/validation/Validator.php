<?php

namespace App\validation;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            foreach ((array) $fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
        switch ($name) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, 'is required');
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, 'must be a string');
                }
                break;
            case 'int':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'must be an integer');
                }
                break;
            case 'email':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError($field, 'must be a valid email');
                }
                break;
            case 'min':
                $min = (int) $param;
                if (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, "must be at least $min characters");
                }
                break;
            case 'max':
                $max = (int) $param;
                if (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, "must be at most $max characters");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
