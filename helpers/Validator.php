<?php
/**
 * Validator Helper
 * Input validation and sanitization for request payloads.
 */

namespace Helpers;

use PDO;

class Validator {
    private array $data;
    private array $rules;
    private array $errors = [];

    public static function getRequestBody(): array {
        $input = file_get_contents('php://input');
        if ((empty($input) || $input === false) && defined('STDIN')) {
            $input = @stream_get_contents(STDIN);
        }
        $decoded = !empty($input) ? json_decode($input, true) : null;
        if (is_array($decoded)) {
            return $decoded;
        }
        return !empty($_POST) ? $_POST : [];
    }

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self {
        $v = new self($data, $rules);
        $v->validate();
        return $v;
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(): ?string {
        if (empty($this->errors)) return null;
        $first = reset($this->errors);
        return is_array($first) ? reset($first) : $first;
    }

    private function validate(): void {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " is required.");
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a valid email address.");
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a number.");
                        }
                        break;

                    case 'min':
                        $min = (float)($params[0] ?? 0);
                        if (is_numeric($value) && (float)$value < $min) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}.");
                        } elseif (is_string($value) && mb_strlen($value) < $min) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.");
                        }
                        break;

                    case 'max':
                        $max = (float)($params[0] ?? 0);
                        if (is_numeric($value) && (float)$value > $max) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max}.");
                        } elseif (is_string($value) && mb_strlen($value) > $max) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max} characters.");
                        }
                        break;

                    case 'in':
                        if (!empty($value) && !in_array($value, $params, false)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be one of: " . implode(', ', $params));
                        }
                        break;

                    case 'unique':
                        // Format: unique:table,column[,ignoreIdColumn,ignoreIdVal]
                        if (!empty($value) && count($params) >= 2) {
                            $table = $params[0];
                            $column = $params[1];
                            $ignoreCol = $params[2] ?? null;
                            $ignoreVal = $params[3] ?? null;

                            $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                            $sqlParams = [':val' => $value];

                            if ($ignoreCol && $ignoreVal) {
                                $sql .= " AND `{$ignoreCol}` != :ignoreVal";
                                $sqlParams[':ignoreVal'] = $ignoreVal;
                            }

                            $db = Database::getInstance();
                            $stmt = $db->prepare($sql);
                            $stmt->execute($sqlParams);
                            if ((int)$stmt->fetchColumn() > 0) {
                                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " already exists.");
                            }
                        }
                        break;
                }
            }
        }
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public static function sanitize(array $input): array {
        $clean = [];
        foreach ($input as $k => $v) {
            if (is_array($v)) {
                $clean[$k] = self::sanitize($v);
            } elseif (is_string($v)) {
                $clean[$k] = trim($v);
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
}
