<?php

namespace Src\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesList = explode('|', $fieldRules);
            $value = $data[$field] ?? null;

            foreach ($rulesList as $rule) {
                if ($rule === 'required') {
                    if (empty($value)) {
                        $this->errors[$field][] = "Le champ $field est requis";
                    }
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($value) < $min) {
                        $this->errors[$field][] = "Le champ $field doit contenir au moins $min caractères";
                    }
                } elseif ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "Le champ $field doit être une adresse email valide";
                    }
                } elseif (strpos($rule, 'same:') === 0) {
                    $otherField = substr($rule, 5);
                    $otherValue = $data[$otherField] ?? null;
                    if ($value !== $otherValue) {
                        $this->errors[$field][] = "Le champ $field doit correspondre au champ $otherField";
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return $allErrors;
    }

    public static function validateEmail($email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public static function validateLength($value, $min): bool
    {
        if (strlen($value) < $min) {
            return false;
        }
        return true;
    }

    public static function validatePassword($password): array
    {
        $errors = [];

        if (!self::validateLength($password, 8)) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }

        return $errors;
    }
}