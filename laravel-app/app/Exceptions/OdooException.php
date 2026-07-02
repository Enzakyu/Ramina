<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Custom exception for Odoo JSON-RPC errors.
 *
 * Wraps error codes, messages, and additional error data returned
 * from Odoo's JSON-RPC responses into a structured exception.
 */
class OdooException extends RuntimeException
{
    /**
     * Additional error data from Odoo response.
     */
    protected array $errorData;

    /**
     * The raw Odoo error type string (e.g. 'access_denied', 'ValidationError').
     */
    protected string $odooErrorType;

    /**
     * @param string         $message       Human-readable error message.
     * @param int            $code          Numeric error code (JSON-RPC or HTTP).
     * @param array          $errorData     Additional debug/trace data from Odoo.
     * @param string         $odooErrorType The Odoo error type identifier.
     * @param Throwable|null $previous      Previous exception for chaining.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        array $errorData = [],
        string $odooErrorType = '',
        ?Throwable $previous = null
    ) {
        $this->errorData = $errorData;
        $this->odooErrorType = $odooErrorType;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Build an OdooException from a decoded JSON-RPC response body.
     *
     * Odoo JSON-RPC error structure:
     * {
     *   "jsonrpc": "2.0",
     *   "id": ...,
     *   "error": {
     *     "code":    200,          // HTTP-level code Odoo chose
     *     "message": "Odoo Server Error",
     *     "data": {
     *       "name":       "odoo.exceptions.AccessDenied",
     *       "message":    "Access denied",
     *       "arguments":  ["Access denied"],
     *       "exception_type": "access_denied",
     *       "debug":      "Traceback ..."
     *     }
     *   }
     * }
     *
     * @param array $response The full decoded JSON-RPC response array.
     * @return static
     */
    public static function fromResponse(array $response): static
    {
        $error = $response['error'] ?? [];

        $rpcCode    = (int) ($error['code'] ?? 0);
        $rpcMessage = $error['message'] ?? 'Unknown Odoo error';
        $data       = $error['data'] ?? [];

        // Prefer the nested data message/name for a more specific description.
        $detailedMessage = $data['message'] ?? $rpcMessage;
        $errorType       = $data['exception_type'] ?? ($data['name'] ?? '');

        // Compose a human-friendly message that includes the error type when available.
        $finalMessage = $errorType
            ? sprintf('[%s] %s', $errorType, $detailedMessage)
            : $detailedMessage;

        return new static(
            message: $finalMessage,
            code: $rpcCode,
            errorData: $data,
            odooErrorType: $errorType,
        );
    }

    /**
     * Build an OdooException from an HTTP transport failure (timeout, DNS, etc.).
     *
     * @param Throwable $exception The underlying Guzzle / transport exception.
     * @return static
     */
    public static function fromTransportError(Throwable $exception): static
    {
        return new static(
            message: 'Odoo connection error: ' . $exception->getMessage(),
            code: (int) $exception->getCode(),
            errorData: [
                'exception_class' => get_class($exception),
            ],
            odooErrorType: 'transport_error',
            previous: $exception,
        );
    }

    /**
     * Get additional Odoo error data (debug trace, arguments, etc.).
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Get the Odoo error type identifier (e.g. 'access_denied', 'ValidationError').
     */
    public function getOdooErrorType(): string
    {
        return $this->odooErrorType;
    }

    /**
     * Get the debug traceback string from Odoo, if available.
     */
    public function getDebugTrace(): string
    {
        return $this->errorData['debug'] ?? '';
    }

    /**
     * Determine whether the error represents an access-denied condition.
     */
    public function isAccessDenied(): bool
    {
        return str_contains($this->odooErrorType, 'access_denied')
            || str_contains($this->getMessage(), 'Access Denied')
            || str_contains($this->getMessage(), 'access_denied');
    }

    /**
     * Determine whether the error represents a validation error.
     */
    public function isValidationError(): bool
    {
        return str_contains($this->odooErrorType, 'ValidationError')
            || str_contains($this->odooErrorType, 'validation_error');
    }

    /**
     * Determine whether the error represents a missing-record error.
     */
    public function isMissingError(): bool
    {
        return str_contains($this->odooErrorType, 'MissingError')
            || str_contains($this->odooErrorType, 'missing_error');
    }
}
