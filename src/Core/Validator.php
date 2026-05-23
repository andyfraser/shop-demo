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
    private array $errors = [];

    public function check(array $data, array $rules): array {
        $this->errors = [];
        $flatErrors = [];

        foreach ($rules as $field => $ruleStr) {
            $rawValue = $data[$field] ?? '';
            $label = ucfirst(str_replace('_', ' ', $field));

            // Type check: handle arrays or other non-scalars safely
            if (is_array($rawValue)) {
                $rulesList = explode('|', $ruleStr);
                foreach ($rulesList as $rule) {
                    [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                    if ($name === 'required') {
                        if (empty($rawValue)) {
                            $err = "$label is required.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                    } else {
                        // Any other rule expects scalar, so arrays are invalid format
                        $err = "Invalid $label format.";
                        if (!in_array($err, $this->errors[$field] ?? [])) {
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                    }
                }
                continue;
            }

            // Safe scalar string casting
            $value = is_scalar($rawValue) ? trim((string)$rawValue) : '';

            foreach (explode('|', $ruleStr) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($value === '') {
                            $err = "$label is required.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'email':
                        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $err = 'Valid email required.';
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'min_length':
                        if ($value !== '' && strlen($value) < (int)$param) {
                            $err = "$label must be at least $param characters.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'max_length':
                        if ($value !== '' && strlen($value) > (int)$param) {
                            $err = "$label must be no more than $param characters.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'positive':
                        if ($value !== '' && (float)$value <= 0) {
                            $err = "$label must be positive.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'min':
                        if ($value !== '' && (float)$value < (float)$param) {
                            $err = "$label must be at least $param.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string)$param);
                        if ($value !== '' && !in_array($value, $allowed, true)) {
                            $err = "Invalid $label.";
                            $this->errors[$field][] = $err;
                            $flatErrors[] = $err;
                        }
                        break;
                }
            }
        }

        return $flatErrors;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFieldError(string $field): ?string {
        return isset($this->errors[$field][0]) ? $this->errors[$field][0] : null;
    }

    public function getFieldErrors(string $field): array {
        return $this->errors[$field] ?? [];
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }
}
