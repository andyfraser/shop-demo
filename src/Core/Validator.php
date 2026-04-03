<?php
namespace App\Core;

/**
 * Simple rule-based validator.
 *
 * Usage:
 *   $errors = Validator::check($_POST, [
 *       'name'     => 'required',
 *       'email'    => 'required|email',
 *       'password' => 'min_length:6',
 *       'price'    => 'required|positive',
 *       'role'     => 'in:admin,customer',
 *   ]);
 *
 * Supported rules:
 *   required        — must be non-empty after trimming
 *   email           — must be a valid email address
 *   min_length:N    — string length must be >= N
 *   max_length:N    — string length must be <= N
 *   positive        — numeric value must be > 0
 *   min:N           — numeric value must be >= N
 *   in:a,b,c        — value must be one of the comma-separated options
 */
class Validator {
    public static function check(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleStr) {
            $value = trim((string)($data[$field] ?? ''));
            $label = ucfirst(str_replace('_', ' ', $field));

            foreach (explode('|', $ruleStr) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($value === '') {
                            $errors[] = "$label is required.";
                        }
                        break;

                    case 'email':
                        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'Valid email required.';
                        }
                        break;

                    case 'min_length':
                        if ($value !== '' && strlen($value) < (int)$param) {
                            $errors[] = "$label must be at least $param characters.";
                        }
                        break;

                    case 'max_length':
                        if ($value !== '' && strlen($value) > (int)$param) {
                            $errors[] = "$label must be no more than $param characters.";
                        }
                        break;

                    case 'positive':
                        if ($value !== '' && (float)$value <= 0) {
                            $errors[] = "$label must be positive.";
                        }
                        break;

                    case 'min':
                        if ($value !== '' && (float)$value < (float)$param) {
                            $errors[] = "$label must be at least $param.";
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string)$param);
                        if ($value !== '' && !in_array($value, $allowed, true)) {
                            $errors[] = "Invalid $label.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
