<?php

/**
 * Common server-side validation class.
 * Used to validate registration, login, product,
 * category and checkout form data.
 */
class Validator
{
    // Store validation errors
    private array $errors = [];

    /**
     * Check whether a required field is empty.
     */
    public function required(
        string $field,
        mixed $value,
        string $message = ''
    ): self {
        // Treat null, empty strings and spaces as empty
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field] =
                $message ?: ucfirst($field) . ' is required.';
        }

        // Return the same Validator object for method chaining
        return $this;
    }

    /**
     * Validate email format.
     */
    public function email(
        string $field,
        mixed $value,
        string $message = ''
    ): self {
        // Only validate format when a value has been provided
        if ($value !== null && trim((string) $value) !== '') {

            // PHP's built-in email validation
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] =
                    $message ?: 'Please enter a valid email address.';
            }
        }

        return $this;
    }

    /**
     * Check minimum string length.
     */
    public function minLength(
        string $field,
        mixed $value,
        int $length,
        string $message = ''
    ): self {
        if ($value !== null && strlen((string) $value) < $length) {
            $this->errors[$field] =
                $message ?: ucfirst($field) .
                " must be at least {$length} characters.";
        }

        return $this;
    }

    /**
     * Check whether two values are exactly the same.
     * Useful for password confirmation.
     */
    public function same(
        string $field,
        mixed $value,
        mixed $otherValue,
        string $message = ''
    ): self {
        if ($value !== $otherValue) {
            $this->errors[$field] =
                $message ?: ucfirst($field) . ' does not match.';
        }

        return $this;
    }

    /**
     * Check whether validation passed without errors.
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Return all validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check whether a specific field has an error.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Return the error message for a specific field.
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
}