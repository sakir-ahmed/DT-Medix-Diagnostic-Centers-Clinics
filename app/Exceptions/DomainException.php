<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule violation with a stable error code (see docs/api.md).
 * Rendered as the JSON error envelope for the API and as a flash error
 * for Inertia requests.
 */
class DomainException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string|int>  $replace
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status = 409,
        public readonly array $details = [],
        ?string $message = null,
        array $replace = [],
    ) {
        parent::__construct($message ?? __('errors.'.$errorCode, $replace));
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string|int>  $replace
     */
    public static function make(string $errorCode, int $status = 409, array $details = [], array $replace = []): self
    {
        return new self($errorCode, $status, $details, null, $replace);
    }
}
